module.exports = {
    darkMode: 'class',
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './src/**/*.php'
    ],
    safelist: [
        'bg-red-500', 'text-green-500', 'rounded-lg', 'p-8'
    ],
    theme: {
        extend: {},
    },
    plugins: [],
};
