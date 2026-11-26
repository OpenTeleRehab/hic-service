<?php

namespace App\Imports;

use App\Helpers\FileHelper;
use App\Models\AdditionalField;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\ExerciseCategory;
use App\Models\File;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Row;
use Throwable;

class ImportExercise implements OnEachRow, WithHeadingRow, WithEvents, WithValidation
{
    const DEFAULT_LANGUAGE = 'en';
    const YES = 'yes';
    const DOWNLOAD_TIMEOUT = 120;
    const MAX_REDIRECTS = 5;

    /**
     * @var string
     */
    private $sheetName = '';

    /**
     * @var array
     */
    private $exerciseIds = [];

    /**
     * @var integer
     */
    private $newRecords = 0;

    /**
     * @var integer
     */
    private $updatedRecords = 0;

    /**
     * Media file URLs that were not imported (unreachable, not public, or type not allowed).
     *
     * @var array
     */
    private $skippedFiles = [];

    /**
     * @param \Maatwebsite\Excel\Row $row
     *
     * @return void
     */
    public function onRow(Row $row): void
    {
        $rowIndex = $row->getIndex();
        $row = $row->toArray();
        $locale = trim($this->sheetName);
        App::setLocale($locale);

        // Insert or update exercise.
        if ($this->sheetName === self::DEFAULT_LANGUAGE) {
            $data = [
                'title' => isset($row['title']) ? $row['title'] : '',
                'include_feedback' => isset($row['collect_sets_and_reps']) ? ($row['collect_sets_and_reps'] === self::YES) : false,
                'get_pain_level' => isset($row['collect_pain_level']) ? ($row['collect_pain_level'] === self::YES) : false,
                'reps' => isset($row['reps']) ? intval($row['reps']) : 0,
                'sets' => isset($row['sets']) ? intval($row['sets']) : 0,
            ];

            $exercise = Exercise::where('id', $row['id'])->updateOrCreate([], $data);
            $this->exerciseIds[$rowIndex] = $exercise->id;

            if ($exercise->wasRecentlyCreated) {
                $this->newRecords++;
                $exercise->is_used = false;
                $exercise->save();
            } else {
                $this->updatedRecords++;
            }

            // Attach categories to exercise.
            ExerciseCategory::where('exercise_id', $exercise->id)->delete();
            $categories = [];

            if (isset($row['categories'])) {
                foreach (explode(',', $row['categories']) as $categoryTree) {
                    $categoriesInTree = explode('->', $categoryTree);
                    $category = trim(end($categoriesInTree));

                    if ($category) {
                        $categories[] = $category;
                    }
                }

                if (count($categories)) {
                    $placeholder = implode(', ', array_fill(0, count($categories), '?'));
                    $categoryIds = Category::whereRaw("JSON_EXTRACT(title, \"$." . self::DEFAULT_LANGUAGE . "\") IN ($placeholder)", $categories)
                        ->pluck('id');

                    $exercise->categories()->attach($categoryIds);
                }
            }

            if (isset($row['dynamic_fields'])) {
                // Insert Additional fields.
                $exercise->additionalFields()->delete();
                AdditionalField::where('exercise_id', $exercise->id)->delete();
                foreach (array_filter(str_getcsv($row['dynamic_fields'], ',')) as $additionalField) {
                    $additionalFieldInfo = explode(':', $additionalField);
                    AdditionalField::create([
                        'field' => trim($additionalFieldInfo[0]),
                        'value' => trim($additionalFieldInfo[1]),
                        'exercise_id' => $exercise->id
                    ]);
                }
            }

            if (isset($row['files'])) {
                // Upload files and attach to exercise.
                $exercise->files()->delete();
                foreach (explode(',', $row['files']) as $index => $fileUrl) {
                    $fileUrl = trim($fileUrl);
                    if (!$fileUrl) {
                        continue;
                    }

                    $uploadedFile = $this->downloadMediaFile($fileUrl);
                    if (!$uploadedFile) {
                        $this->skippedFiles[] = ['row' => $rowIndex, 'url' => $fileUrl];
                        continue;
                    }

                    try {
                        // Skip files whose type is not allowed.
                        if (FileHelper::isAllowedFileType($uploadedFile)) {
                            $file = FileHelper::createFile($uploadedFile, File::EXERCISE_PATH, File::EXERCISE_THUMBNAIL_PATH);
                            $exercise->files()->attach($file->id, ['order' => (int)$index]);
                        } else {
                            $this->skippedFiles[] = ['row' => $rowIndex, 'url' => $fileUrl];
                        }
                    } finally {
                        // Also when storing fails (e.g. thumbnail generation), so temp files do not pile up.
                        @unlink($uploadedFile->getPathname());
                    }
                }
            }
        } else {
            if (isset($this->exerciseIds[$rowIndex])) {
                $data = [
                    'title' => $row['title'],
                ];
                Exercise::find($this->exerciseIds[$rowIndex])->update($data);
            }
        }
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->sheetName = $event->getSheet()->getTitle();
            }
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'title' => 'required',
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'title.required' => 'error_message.exercise_bulk_upload_title_required',
        ];
    }

    /**
     * @return array
     */
    public function getImportInfo()
    {
        return [
            'new_records' => $this->newRecords,
            'updated_records' => $this->updatedRecords,
            'skipped_files' => $this->skippedFiles,
        ];
    }

    /**
     * @return string
     */
    public function getCurrentSheetName()
    {
        return $this->sheetName;
    }

    /**
     * Download a media file listed in the sheet to a temporary file.
     *
     * Only public http(s) hosts are fetched, so the import cannot be used to read local files or reach
     * internal services. Redirects are followed one hop at a time: each hop's host is resolved and checked,
     * and the connection is pinned to the checked IP (CURLOPT_RESOLVE), so a DNS answer that changes
     * between the check and the request (DNS rebinding) cannot send it elsewhere.
     *
     * @param string $url
     *
     * @return \Illuminate\Http\UploadedFile|null
     */
    private function downloadMediaFile(string $url)
    {
        $fileName = basename((string) parse_url($url, PHP_URL_PATH));
        // Without cURL, Guzzle would ignore the pinned IP.
        if ($fileName === '' || !extension_loaded('curl')) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'exercise_import_');
        $response = null;

        try {
            for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
                $resolve = $this->resolvePublicHost($url);
                if (!$resolve) {
                    break;
                }

                $response = Http::timeout(self::DOWNLOAD_TIMEOUT)
                    ->sink($tempPath)
                    ->withOptions([
                        'allow_redirects' => false,
                        'curl' => [
                            CURLOPT_RESOLVE => [$resolve],
                            // Only IPv4 addresses are checked, so never connect over IPv6.
                            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                        ],
                    ])
                    ->get($url);

                $location = $response->header('Location');
                if (!$response->redirect() || $location === '') {
                    break;
                }

                $url = (string) UriResolver::resolve(new Uri($url), new Uri($location));
                $response = null;
            }
        } catch (Throwable $e) {
            $response = null;
        }

        if (!$response || !$response->successful() || !filesize($tempPath)) {
            @unlink($tempPath);
            return null;
        }

        // test mode: the file was not received through a PHP upload, so is_uploaded_file() does not apply.
        return new UploadedFile($tempPath, $fileName, null, null, true);
    }

    /**
     * Resolve the host of a http(s) URL and check that all its addresses are public.
     *
     * @param string $url
     *
     * @return string|null a CURLOPT_RESOLVE entry ("host:port:ip") pinning the checked address, or null
     */
    private function resolvePublicHost(string $url)
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        // IPv6 literals ("[::1]") are refused: only IPv4 is resolved and checked.
        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || str_contains($host, ':') || str_starts_with($host, '[')) {
            return null;
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? [$host] : (gethostbynamel($host) ?: []);
        if (!$ips) {
            return null;
        }

        foreach ($ips as $ip) {
            $isPublic = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_GLOBAL_RANGE | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
            if (!$isPublic) {
                return null;
            }
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        return "$host:$port:$ips[0]";
    }
}
