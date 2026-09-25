<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Inventory Management System</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="text-center max-w-md mx-auto">
            <div class="mx-auto mb-6 w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center">
                <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <h1 class="text-6xl font-bold text-red-600">403</h1>
            <p class="text-xl text-gray-700 font-semibold mt-4">Access Denied</p>
            <p class="text-gray-500 mt-2">You do not have permission to access this page.</p>
            <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-md hover:bg-blue-700 hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15l3-3m0 0l3 3m-3-3v8m6-4.5V6.75A2.25 2.25 0 0015.75 4.5h-7.5A2.25 2.25 0 006 6.75V16.5" />
                </svg>
                Go to Dashboard
            </a>
        </div>
    </div>
</body>
</html>