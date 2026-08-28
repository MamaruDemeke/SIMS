<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Inventory Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-red-600">403</h1>
        <p class="text-xl text-gray-600 mt-4">Access Denied</p>
        <p class="text-gray-500 mt-2">You do not have permission to access this page.</p>
        <a href="{{ route('dashboard') }}" class="mt-6 inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">Go to Dashboard</a>
    </div>
</body>
</html>
