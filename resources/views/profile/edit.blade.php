<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
    <style>
        .img-container img {
            max-width: 100%;
        }
        
        /* Modal backdrop */
        #cropper-modal-backdrop {
            z-index: 9998 !important;
        }
        
        /* Modal container */
        #cropper-modal {
            z-index: 9999 !important;
        }
        
        /* Modal content wrapper */
        #cropper-modal .modal-content-wrapper {
            position: relative;
            z-index: 10000 !important;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const avatarUpload = document.getElementById('avatar-upload');
            const cropperModal = document.getElementById('cropper-modal');
            const cropperModalBackdrop = document.getElementById('cropper-modal-backdrop');
            const cropperImage = document.getElementById('cropper-image');
            const cropBtn = document.getElementById('crop-btn');
            const cancelCropBtn = document.getElementById('cancel-crop-btn');
            let cropper;

            if (!avatarUpload || !cropperModal || !cropperImage) {
                console.error('Required elements not found');
                return;
            }

            avatarUpload.addEventListener('change', function (e) {
                const files = e.target.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    
                    // Validate file type
                    if (!file.type.match('image.*')) {
                        alert('Please select an image file');
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        cropperImage.src = e.target.result;
                        
                        // Show modal and backdrop
                        cropperModal.classList.remove('hidden');
                        if (cropperModalBackdrop) {
                            cropperModalBackdrop.classList.remove('hidden');
                        }
                        
                        // Destroy existing cropper if any
                        if (cropper) {
                            cropper.destroy();
                        }
                        
                        // Initialize cropper after image loads
                        cropperImage.onload = function() {
                            cropper = new Cropper(cropperImage, {
                                aspectRatio: 1,
                                viewMode: 1,
                                autoCropArea: 1,
                                responsive: true,
                                restore: false,
                                guides: true,
                                center: true,
                                highlight: false,
                                cropBoxMovable: true,
                                cropBoxResizable: true,
                                toggleDragModeOnDblclick: false,
                            });
                        };
                    };
                    reader.readAsDataURL(file);
                }
            });

            function closeModal(clearInput = true) {
                cropperModal.classList.add('hidden');
                if (cropperModalBackdrop) {
                    cropperModalBackdrop.classList.add('hidden');
                }
                if (clearInput) {
                    avatarUpload.value = '';
                }
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            }

            cancelCropBtn.addEventListener('click', () => closeModal(true));

            cropBtn.addEventListener('click', function () {
                if (!cropper) {
                    console.error('Cropper not initialized');
                    return;
                }

                const canvas = cropper.getCroppedCanvas({
                    width: 300,
                    height: 300,
                });

                if (!canvas) {
                    console.error('Failed to get cropped canvas');
                    return;
                }

                canvas.toBlob(function (blob) {
                    if (!blob) {
                        console.error('Failed to create blob');
                        return;
                    }

                    // Create a new File object
                    const file = new File([blob], "avatar.png", { type: "image/png" });
                    
                    // Create a DataTransfer to simulate file input
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    avatarUpload.files = dataTransfer.files;

                    // Update preview immediately
                    const preview = document.querySelector('img[alt="{{ $user->name }}"]');
                    if (preview) {
                        preview.src = canvas.toDataURL();
                    }

                    // Close modal without clearing input
                    closeModal(false);
                }, 'image/png');
            });

            // Close modal when clicking backdrop
            if (cropperModalBackdrop) {
                cropperModalBackdrop.addEventListener('click', () => closeModal(true));
            }
        });
    </script>
    @endpush
</x-app-layout>