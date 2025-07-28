<?php

/**
 * Generate a URL with the correct base path
 * 
 * @param string $path The path to append to the base URL
 * @return string The full URL with base path
 */
function base_url($path = '') {
    // Remove leading slash if present
    if (substr($path, 0, 1) === '/') {
        $path = substr($path, 1);
    }
    
    return '/platform_gmao/' . $path;
}

/**
 * Generate a route URL
 * 
 * @param string $route The route name
 * @param array $params Additional query parameters
 * @return string The full URL with route
 */
function route_url($route, $params = []) {
    $url = base_url('index.php?route=' . $route);
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $url .= '&' . urlencode($key) . '=' . urlencode($value);
        }
    }
    
    return $url;
}

/**
 * Generate an asset URL
 * 
 * @param string $path The asset path
 * @return string The full URL to the asset
 */
function asset_url($path) {
    // Remove leading slash if present
    if (substr($path, 0, 1) === '/') {
        $path = substr($path, 1);
    }
    
    return base_url($path);
} 