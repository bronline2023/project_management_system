<?php
// views/home.php
// This is the default home page content for unauthenticated users.
?>
<div class="container mt-5">
    <div class="jumbotron text-center">
        <h1 class="display-4">Welcome to Project Management System!</h1>
        <p class="lead">Efficiently manage your projects, tasks, and team members.</p>
        <hr class="my-4">
        <p>Please log in to access your dashboard and manage your work.</p>
        <a class="btn btn-primary btn-lg rounded-pill" href="<?= BASE_URL ?>?page=login" role="button">Log In</a>
        <a class="btn btn-outline-secondary btn-lg rounded-pill ms-2" href="<?= BASE_URL ?>?page=register" role="button">Register</a>
    </div>
</div>