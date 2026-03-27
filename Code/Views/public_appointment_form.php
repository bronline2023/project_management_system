<div class="card login-card p-4" style="max-width: 450px;">
    <div class="card-body text-white">
        <div class="text-center mb-4">
            <h4 class="card-title">Book an Appointment</h4>
            <p>Schedule a visit to our office.</p>
        </div>
        <form action="index.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="book_appointment">
            <div class="mb-3"><input type="text" class="form-control" name="client_name" placeholder="Your Name" required></div>
            <div class="mb-3"><input type="tel" class="form-control" name="client_phone" placeholder="Your Phone Number" required></div>
            <div class="mb-3"><select class="form-select" name="category_id" required><option value="">Select Service</option><?php foreach(fetchAll(connectDB(), "SELECT id, name FROM categories") as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option><?php endforeach; ?></select></div>
            <div class="mb-3"><select class="form-select" name="user_id" required><option value="">Whom to Meet?</option><?php foreach(fetchAll(connectDB(), "SELECT id, name FROM users WHERE role_id != 1") as $user): ?><option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option><?php endforeach; ?></select></div>
            <div class="mb-3"><label class="form-label">Appointment Date</label><input type="date" class="form-control" name="appointment_date" required></div>
            <div class="mb-3"><label class="form-label">Appointment Time</label><select class="form-select" name="appointment_time" required><option value="">Select Time Slot</option></select></div>
            <div class="mb-3"><label class="form-label">Attach Document</label><input type="file" class="form-control" name="document"></div>
            <div class="mb-3"><textarea class="form-control" name="notes" rows="2" placeholder="Notes about your work..."></textarea></div>
            <div class="d-grid"><button type="submit" class="btn btn-success">Book Appointment</button></div>
        </form>
    </div>
</div>