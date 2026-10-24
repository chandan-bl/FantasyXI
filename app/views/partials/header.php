<?php
/** @var string|null $pageTitle */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Dashboard') ?> &middot; FantasyXI</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><circle cx=%2250%22 cy=%2250%22 r=%2246%22 fill=%22%23F0A202%22/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Teko:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    indigo: {
                        ink: '#1E1B4B',
                        DEFAULT: '#4338CA',
                        dark: '#362FA0',
                        50: '#EEECFB',
                        100: '#DEDAF6',
                    },
                    saffron: {
                        DEFAULT: '#F0A202',
                        dark: '#C97F0A',
                        50: '#FDF3E1',
                    },
                    canvas: '#FAF7F2',
                    ink: '#1E1B33',
                    muted: '#635F7A',
                    line: '#E7E2F5',
                },
                fontFamily: {
                    display: ['"Teko"', 'sans-serif'],
                    body: ['"Manrope"', 'sans-serif'],
                },
            },
        },
    };
</script>
<link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="font-body bg-canvas text-ink min-h-screen flex flex-col">
