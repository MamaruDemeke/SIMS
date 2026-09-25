<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - YEGNA TRADING PLC</title>
    {{-- Load the compiled CSS. @vite is Laravel's asset bundler; it injects the CSS <link> tag. --}}
    @vite(['resources/css/app.css'])
</head>
{{-- This page has NO sidebar/layout because the user isn't logged in yet.
     Blades (Tailwind) classes are used to center the login card vertically & horizontally.
     If a company logo has been uploaded, it is shown as a soft background image
     and inside the login card header. --}}
@php $logo = company_logo(); @endphp
<body class="min-h-screen flex items-center justify-center"
      style="@if($logo) background-image:url('{{ $logo }}'); background-size:90% auto; background-position:90% 12%; background-repeat:no-repeat; background-attachment:fixed; @else background-color:#f3f4f6; @endif">
    {{-- Slight dark overlay so the white card stays readable over the logo. --}}
    @if($logo)
        <div class="fixed inset-0 bg-black/30"></div>
    @endif
    <div class="w-full max-w-md relative">
        <div class="bg-white rounded-2xl shadow-2xl p-8 relative overflow-hidden">
            {{-- Top accent gradient strip --}}
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-600 via-blue-500 to-blue-400"></div>
            {{-- Login card header --}}
            <div class="text-center mb-8">
                @if($logo)
                    <img src="{{ $logo }}" alt="Company Logo" class="mx-auto h-14 object-contain mb-3">
                @else
                    <div class="mx-auto mb-4 w-14 h-14 bg-gradient-to-br from-blue-600 to-blue-500 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-600/30">
                        <span class="text-white font-bold text-lg">YT</span>
                    </div>
                @endif
                <h1 class="text-2xl font-bold text-gray-800">YEGNA TRADING PLC</h1>
                <p class="text-sm text-gray-500 mt-1">Inventory Management System</p>
            </div>

            {{-- The form. method="POST" + action="{{ route('login') }}" means:
                 when submitted, it sends a POST request to the /login URL,
                 which is handled by LoginController@login (see routes/web.php). --}}
            <form method="POST" action="{{ route('login') }}" id="login-form">
                {{-- @csrf inserts a hidden CSRF token. It's REQUIRED for POST forms —
                     it stops attackers from submitting forged requests on the user's behalf. --}}
                @csrf

                {{-- Email field --}}
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        {{-- old('email') re-fills the typed email if the form was rejected --
                             so the user doesn't have to retype it. --}}
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="you@yegnatrading.com"
                        {{-- Tailwind classes. The @error('email') ... @error block turns the
                             border red if there is an "email" validation error. --}}
                        class="w-full px-3.5 py-2.5 border @error('email') border-red-400 @else border-gray-200 @enderror rounded-lg bg-gray-50 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-colors"
                    >
                    {{-- Show the error message under the field if one exists. --}}
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password field, wrapped in a relative container so the show/hide-eye button
                     can be positioned inside the input on the right side. --}}
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            class="w-full px-3.5 py-2.5 pr-10 border @error('password') border-red-400 @else border-gray-200 @enderror rounded-lg bg-gray-50 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-colors"
                        >
                        {{-- Toggle eye button. onclick calls togglePassword() at the bottom.
                             tabindex="-1" keeps it out of the keyboard tab order since it's cosmetic. --}}
                        <button type="button" onclick="togglePassword('password', this)"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600" tabindex="-1" aria-label="Show password">
                            {{-- "Eye" (open) icon — visible by default, meaning press to SHOW the password. --}}
                            <svg id="password-eye" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{-- "Eye-off" (slashed) icon — hidden by default, appears when the password is shown. --}}
                            <svg id="password-eye-off" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    {{-- Show the password error message here (e.g. "password does not match"). --}}
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Countdown message: shown when too many failed attempts locked the form.
                     The server flashes session('retry_after') with the seconds to wait. --}}
                @if(session('retry_after'))
                <div id="lock-msg" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-md text-sm text-center">
                    Too many failed attempts. Please wait
                    <span id="lock-count" class="font-bold">{{ session('retry_after') }}</span> seconds.
                </div>
                @endif

                {{-- Submit button --}}
                <button
                    type="submit"
                    id="login-btn"
                    class="w-full bg-blue-600 text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-150 ease-in-out shadow-md"
                >
                    Login
                </button>
            </form>
        </div>
    </div>

    {{-- JavaScript: toggles the password visibility.
         It switches the input between type="password" and type="text",
         and swaps the eye / eye-off icons. --}}
    <script>
        function togglePassword(fieldId, btn) {
            const input = document.getElementById(fieldId); // the password input
            const isPassword = input.type === 'password';   // is it currently hidden?
            input.type = isPassword ? 'text' : 'password';  // toggle the type
            // Show/hide the two icons to match the current state.
            btn.querySelector('#password-eye').classList.toggle('hidden', isPassword);
            btn.querySelector('#password-eye-off').classList.toggle('hidden', !isPassword);
        }

        // ==== LOCKOUT COUNTDOWN ====
        // If the server flashed session('retry_after'), we disable the form inputs
        // and count down from that many seconds. When it reaches 0, we re-enable.
        @if(session('retry_after'))
        (function countdown() {
            let seconds = {{ session('retry_after') }};   // seconds to wait (e.g. 30)
            const form  = document.getElementById('login-form');
            const btn   = document.getElementById('login-btn');
            const span  = document.getElementById('lock-count');

            // Disable all inputs + the button so nothing can be submitted meanwhile.
            form.querySelectorAll('input, button').forEach(el => el.disabled = true);
            btn.textContent = 'Wait ' + seconds + 's';

            const timer = setInterval(function () {
                seconds--;
                if (span) span.textContent = seconds;
                btn.textContent = 'Wait ' + seconds + 's';

                if (seconds <= 0) {
                    clearInterval(timer);          // stop the timer
                    btn.textContent = 'Login';     // restore button text
                    // Re-enable all inputs + the button for retry.
                    form.querySelectorAll('input, button').forEach(el => el.disabled = false);
                    // Remove the lock message box.
                    const msg = document.getElementById('lock-msg');
                    if (msg) msg.remove();
                }
            }, 1000);
        })();
        @endif
    </script>
</body>
</html>
