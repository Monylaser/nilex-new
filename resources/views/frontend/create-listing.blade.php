<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Listing - Multi Upload</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Cairo Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- FilePond CSS (with RTL support) -->
    <link href="https://unpkg.com/filepond@4.30.4/dist/filepond.css" rel="stylesheet">
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        /* Fix for Chrome RTL click issues */
        .filepond--root {
            direction: ltr !important; /* FilePond works better in LTR internally */
        }
        .filepond--root * {
            direction: ltr !important;
        }
        /* Custom RTL text for labels */
        .filepond--label-action {
            font-family: 'Cairo', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6 text-right">إضافة قائمة جديدة</h1>

            <form id="listingForm" method="POST" action="{{ route('listings.store') }}" enctype="multipart/form-data">
                @csrf

                <!-- Title Field -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2 text-right">العنوان</label>
                    <input type="text" name="title" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-right" required>
                </div>

                <!-- Description Field -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2 text-right">الوصف</label>
                    <textarea name="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-right"></textarea>
                </div>

                <!-- FilePond Upload Field -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2 text-right">الصور</label>
                    <input type="file" name="images[]" id="filepond-input" multiple accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                    <p class="text-sm text-gray-500 mt-1 text-right">يمكنك رفع عدة صور (JPG, PNG, GIF, WebP)</p>
                </div>

                <!-- Submit Button -->
                <div class="mt-6 text-center">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-lg transition duration-300">
                        حفظ القائمة
                    </button>
                </div>
            </form>

            <!-- Preview uploaded images after submit -->
            @if(session('uploaded_images'))
            <div class="mt-8 pt-4 border-t">
                <h3 class="font-bold mb-3 text-right">الصور المرفوعة:</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach(session('uploaded_images') as $image)
                    <div class="relative">
                        <img src="{{ asset($image) }}" class="w-full h-32 object-cover rounded-lg" alt="Uploaded image">
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- FilePond Scripts (with cache-busting) -->
    <script src="https://unpkg.com/filepond@4.30.4/dist/filepond.js?ver={{ time() }}"></script>
    <script src="https://unpkg.com/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.js?ver={{ time() }}"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-type@1.2.8/dist/filepond-plugin-file-validate-type.js?ver={{ time() }}"></script>

    <script>
        // Register plugins
        FilePond.registerPlugin(FilePondPluginImagePreview);
        FilePond.registerPlugin(FilePondPluginFileValidateType);

        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Force destroy any existing instances
            const existingPond = FilePond.find(document.getElementById('filepond-input'));
            if (existingPond) {
                existingPond.destroy();
            }

            // Initialize FilePond
            const pond = FilePond.create(document.getElementById('filepond-input'), {
                // Core settings
                allowMultiple: true,
                allowReorder: true,
                allowImagePreview: true,
                allowFileTypeValidation: true,

                // File validation
                acceptedFileTypes: ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'],
                fileValidateTypeLabelExpectedTypes: 'يجب أن تكون الصور بصيغة JPG, PNG, GIF, أو WebP',

                // RTL support workaround
                dir: 'rtl',
                labelIdle: 'اسحب وأفلت الصور هنا أو <span class="filepond--label-action">اختر الصور</span>',
                labelFileProcessing: 'جاري الرفع...',
                labelFileProcessingComplete: 'تم الرفع بنجاح',
                labelFileProcessingAborted: 'تم الإلغاء',
                labelTapToCancel: 'انقر للإلغاء',
                labelTapToRetry: 'انقر لإعادة المحاولة',
                labelTapToUndo: 'انقر للتراجع',

                // Server configuration for AJAX upload (optional - for instant upload)
                // If you want instant upload on file add, uncomment this:
                /*
                server: {
                    url: '{{ route("listings.upload-temp") }}',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    process: {
                        url: '/upload',
                        method: 'POST',
                        onload: (response) => {
                            console.log('Upload complete:', response);
                            return response;
                        }
                    }
                }
                */
            });

            // Debug: Log when files are added
            pond.on('addfile', (error, file) => {
                if (error) {
                    console.error('FilePond addfile error:', error);
                } else {
                    console.log('File added:', file.filename);
                    console.log('Total files:', pond.getFiles().length);
                }
            });

            // Ensure multiple selection works by overriding the browse button behavior
            const browseButton = document.querySelector('.filepond--browse');
            if (browseButton) {
                browseButton.addEventListener('click', (e) => {
                    // Create a temporary file input with multiple attribute
                    const tempInput = document.createElement('input');
                    tempInput.type = 'file';
                    tempInput.multiple = true;
                    tempInput.accept = 'image/jpeg,image/png,image/jpg,image/gif,image/webp';

                    tempInput.addEventListener('change', (e) => {
                        const files = Array.from(e.target.files);
                        files.forEach(file => {
                            pond.addFile(file);
                        });
                        tempInput.remove();
                    });

                    tempInput.click();
                    e.preventDefault();
                    e.stopPropagation();
                });
            }
        });

        // Form submission handler - ensure all files are included
        document.getElementById('listingForm').addEventListener('submit', function(e) {
            const pond = FilePond.find(document.getElementById('filepond-input'));
            if (pond && pond.getFiles().length > 0) {
                // FilePond handles the files automatically when using standard form submission
                console.log(`Submitting ${pond.getFiles().length} files`);
            }
        });
    </script>
</body>
</html>
