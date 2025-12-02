<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div>
            <x-input-label :value="__('Profile Image')" />
            
            <div class="mt-2 flex items-center gap-4">
                <div class="relative">
                    @if($user->avatar)
                        <img src="{{ Str::startsWith($user->avatar, 'http') ? $user->avatar : asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-full object-cover border">
                    @else
                        <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                            <i class="fi fi-rr-user text-2xl"></i>
                        </div>
                    @endif
                </div>
                
                <div class="flex-1">
                    <!-- File Upload -->
                    <input type="file" id="avatar-upload" name="avatar" class="hidden" accept="image/*">
                    <button type="button" onclick="document.getElementById('avatar-upload').click()" class="btn btn-outline-secondary btn-sm mb-2">
                        Upload from System
                    </button>

                    <!-- URL Input -->
                    <div class="flex gap-2">
                        <x-text-input id="avatar_url" name="avatar_url" type="url" class="block w-full text-sm" placeholder="Or paste image link..." :value="old('avatar_url')" />
                    </div>
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
            <x-input-error class="mt-2" :messages="$errors->get('avatar_url')" />
        </div>

        <!-- Cropper Modal Backdrop (Separate from modal) -->
        <div id="cropper-modal-backdrop" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity hidden" style="z-index: 9998;" aria-hidden="true"></div>

        <!-- Cropper Modal -->
        <div id="cropper-modal" class="fixed inset-0 overflow-y-auto hidden" style="z-index: 9999;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="modal-content-wrapper inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" style="position: relative; z-index: 10000;">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Crop Your Image</h3>
                        <div class="img-container">
                            <img id="cropper-image" src="" style="max-width: 100%;">
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" id="crop-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Crop & Save
                        </button>
                        <button type="button" id="cancel-crop-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>