<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Dear {{ $first_name }}:
                </div>
                <div class="card-body">
                    <p>
                        Thank you for using the OpenTeleRehab Library and for submitting a resource.
                        Please click on the link below to complete your submission, our team will review the submitted resource and will be published in the library once it is approved.
                    </p>
                    <a href="{{ $url }}">{{ $url }}</a>
                </div>
                <br>
                <br>
                Thank you!
                <br>
                <br>
                OpenTeleRehab Library Team
            </div>
        </div>
    </div>
</div>
