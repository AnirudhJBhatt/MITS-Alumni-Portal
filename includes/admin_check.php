<?php
/**
 * Administrator Authorization Gatekeeper
 * MITS Alumni Portal
 */

require_once __DIR__ . '/auth_check.php';

if (!is_admin()) {
    set_flash('danger', 'Access denied. Administrator privileges are required to view that page.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
