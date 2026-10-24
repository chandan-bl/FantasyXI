<?php

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $route): void
{
    // A route can itself carry a query string (e.g. "manager/team/build?contest_id=5").
    // Folding it straight into "?route=" without care turns everything after the "?"
    // into extra top-level query params instead of part of the route value, so split
    // it out and re-append it as its own param -- a bug that bit an earlier project
    // in this suite (dsa-visualizer-tracer); applying the fix proactively here.
    if (str_contains($route, '?')) {
        [$path, $query] = explode('?', $route, 2);
        header('Location: /index.php?route=' . $path . '&' . $query);
    } else {
        header('Location: /index.php?route=' . $route);
    }
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function flash_get(string $type): ?string
{
    if (empty($_SESSION['flash'][$type])) {
        return null;
    }
    $message = $_SESSION['flash'][$type];
    unset($_SESSION['flash'][$type]);
    return $message;
}

function old(array $data, string $key, string $default = ''): string
{
    return e((string)($data[$key] ?? $default));
}

/**
 * One-shot flash of a just-rejected form submission, so a validation-failure
 * redirect can re-render exactly what the user picked instead of falling
 * back to whatever's already saved (or nothing, for a brand new form).
 */
function old_input_set(array $data): void
{
    $_SESSION['old_input'] = $data;
}

function old_input_get(): ?array
{
    if (empty($_SESSION['old_input'])) {
        return null;
    }
    $data = $_SESSION['old_input'];
    unset($_SESSION['old_input']);
    return $data;
}

function format_datetime(?string $datetime): string
{
    if (empty($datetime)) {
        return '-';
    }
    $ts = strtotime($datetime);
    return $ts === false ? '-' : date('d M Y, h:i A', $ts);
}

function format_credits(float|string $value): string
{
    return number_format((float)$value, 1);
}

function format_points(float|string $value): string
{
    $value = (float)$value;
    $sign = $value > 0 ? '+' : '';
    return $sign . number_format($value, 1);
}

function role_label(string $role): string
{
    return match ($role) {
        'wicketkeeper' => 'Wicketkeeper',
        'batter'       => 'Batter',
        'allrounder'   => 'All-rounder',
        'bowler'       => 'Bowler',
        default        => ucfirst($role),
    };
}

function role_badge_classes(string $role): string
{
    return match ($role) {
        'wicketkeeper' => 'bg-amber-100 text-amber-800 border-amber-300',
        'batter'       => 'bg-indigo-100 text-indigo-800 border-indigo-300',
        'allrounder'   => 'bg-emerald-100 text-emerald-800 border-emerald-300',
        'bowler'       => 'bg-rose-100 text-rose-800 border-rose-300',
        default        => 'bg-slate-100 text-slate-800 border-slate-300',
    };
}

/**
 * Real-world-ish IPL franchise brand colors, keyed by franchise name, used for
 * chips/badges across the UI so each franchise reads as itself at a glance
 * rather than a random palette color.
 */
function franchise_colors(string $name): array
{
    $map = [
        'Mumbai Indians'                 => ['bg' => '#004BA0', 'fg' => '#FFD700'],
        'Chennai Super Kings'            => ['bg' => '#F9CD05', 'fg' => '#0A2240'],
        'Royal Challengers Bengaluru'    => ['bg' => '#8B0000', 'fg' => '#FFD700'],
        'Kolkata Knight Riders'          => ['bg' => '#3A225D', 'fg' => '#D4AF37'],
        'Delhi Capitals'                 => ['bg' => '#17479E', 'fg' => '#EF1C25'],
        'Punjab Kings'                   => ['bg' => '#DD1F2D', 'fg' => '#F2E9D8'],
        'Rajasthan Royals'               => ['bg' => '#254AA5', 'fg' => '#EA1E88'],
        'Sunrisers Hyderabad'            => ['bg' => '#F26522', 'fg' => '#111111'],
        'Gujarat Titans'                 => ['bg' => '#1B2133', 'fg' => '#B4975A'],
        'Lucknow Super Giants'           => ['bg' => '#00AEEF', 'fg' => '#132743'],
    ];

    return $map[$name] ?? ['bg' => '#4338CA', 'fg' => '#FDE68A'];
}

function franchise_chip(string $name, string $code): string
{
    $c = franchise_colors($name);
    return sprintf(
        '<span class="franchise-chip" style="background:%s;color:%s" title="%s">%s</span>',
        e($c['bg']), e($c['fg']), e($name), e($code)
    );
}

function role_badge(string $role): string
{
    return sprintf('<span class="role-badge %s">%s</span>', role_badge_classes($role), e(role_label($role)));
}

function contest_status_pill(array $contest): string
{
    if ($contest['status'] === 'completed') {
        return '<span class="pill pill-completed">Completed</span>';
    }
    if (is_contest_locked($contest['match_datetime'])) {
        return '<span class="pill pill-locked">Locked &middot; awaiting stats</span>';
    }
    $ts = strtotime($contest['match_datetime']);
    $hoursLeft = ($ts - time()) / 3600;
    $cls = $hoursLeft < 24 ? 'pill-soon' : 'pill-open';
    return '<span class="pill ' . $cls . '">' . e(time_until($contest['match_datetime'])) . '</span>';
}

function time_until(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '-';
    }
    $diff = $ts - time();
    if ($diff <= 0) {
        return 'Locked';
    }
    $days = intdiv($diff, 86400);
    $hours = intdiv($diff % 86400, 3600);
    $mins = intdiv($diff % 3600, 60);
    if ($days > 0) {
        return "{$days}d {$hours}h left";
    }
    if ($hours > 0) {
        return "{$hours}h {$mins}m left";
    }
    return "{$mins}m left";
}

function is_contest_locked(string $matchDatetime): bool
{
    $ts = strtotime($matchDatetime);
    return $ts === false || $ts <= time();
}
