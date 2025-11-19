@component('layouts.app')
    @slot('title', __('apps.title'))
    <div class="container">
        <h1 class="title">@lang('apps.header')</h1>

        <div class="columns">
            <div class="column is-half">
                <div class="box content">
                    <h2 class="title is-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="image is-small is-inline" viewBox="0 0 24 24" style="transform: translateY(2px);">
                            <path d="M16.61 15.15C16.15 15.15 15.77 14.78 15.77 14.32S16.15 13.5 16.61 13.5H16.61C17.07 13.5 17.45 13.86 17.45 14.32C17.45 14.78 17.07 15.15 16.61 15.15M7.41 15.15C6.95 15.15 6.57 14.78 6.57 14.32C6.57 13.86 6.95 13.5 7.41 13.5H7.41C7.87 13.5 8.24 13.86 8.24 14.32C8.24 14.78 7.87 15.15 7.41 15.15M16.91 10.14L18.58 7.26C18.67 7.09 18.61 6.88 18.45 6.79C18.28 6.69 18.07 6.75 18 6.92L16.29 9.83C14.95 9.22 13.5 8.9 12 8.91C10.47 8.91 9 9.24 7.73 9.82L6.04 6.91C5.95 6.74 5.74 6.68 5.57 6.78C5.4 6.87 5.35 7.08 5.44 7.25L7.1 10.13C4.25 11.69 2.29 14.58 2 18H22C21.72 14.59 19.77 11.7 16.91 10.14H16.91Z" />
                        </svg>
                        @lang('apps.android_header')
                    </h2>

                    <p>@lang('apps.android_description_one')</p>
                    <p class="mb-2">@lang('apps.android_description_two')</p>

                    <p>
                        <a href="https://play.google.com/store/apps/details?id=nl.plaatsoft.strepen" target="_blank" rel="noreferrer">
                            <img alt="Get it on Google Play" src="/images/google-play-download.png" style="width: 250px;">
                        </a>

                        <a href="https://github.com/diekantankys/strepen/releases/tag/client-v1.6.0" target="_blank" rel="noreferrer">
                            <img alt="Get it on Google Play" src="/images/direct-apk-download.png" style="width: 250px;">
                        </a>
                    </p>
                </div>
            </div>

            <div class="column is-half">
                <div class="box content">
                    <h2 class="title is-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="image is-small is-inline" viewBox="0 0 24 24" style="transform: translateY(2px);">
                            <path d="M3,12V6.75L9,5.43V11.91L3,12M20,3V11.75L10,11.9V5.21L20,3M3,13L9,13.09V19.9L3,18.75V13M20,13.25V22L10,20.09V13.1L20,13.25Z" />
                        </svg>
                        @lang('apps.windows_header')
                    </h2>

                    <p>@lang('apps.windows_description_one')</p>
                    <p class="mb-5">@lang('apps.windows_description_two')</p>

                    <div class="buttons">
                        <a class="button is-link p-4" href="https://github.com/diekantankys/strepen/releases/download/windows-client-v1.6.0/strepen-win64.zip">
                            <svg xmlns="http://www.w3.org/2000/svg" class="image is-small is-inline" viewBox="0 0 24 24" style="transform: translateY(1px);">
                                <path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" />
                            </svg>
                            @lang('apps.windows_download_app')
                        </a>

                        <a class="button is-link p-4" href="https://github.com/diekantankys/strepen/releases/download/windows-client-v1.6.0/MicrosoftEdgeWebview2Setup.exe">
                            <svg xmlns="http://www.w3.org/2000/svg" class="image is-small is-inline" viewBox="0 0 24 24" style="transform: translateY(1px);">
                                <path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" />
                            </svg>
                            @lang('apps.windows_download_webview2')
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcomponent
