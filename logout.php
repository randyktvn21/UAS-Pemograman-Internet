<?php

declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
logout_user();
flash('success', 'Anda berhasil keluar dari aplikasi.');
redirect('login.php');
