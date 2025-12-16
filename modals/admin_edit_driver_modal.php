<!-- Edit Driver Modal -->
<div class="modal fade" id="editDriverModal" tabindex="-1" aria-labelledby="editDriverModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="update_driver.php" id="editDriverForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDriverModalLabel">Edit Driver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="driver_id" id="editDriverId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editDriverFirstName" class="form-label">First Name</label>
                            <input type="text" name="first_name" id="editDriverFirstName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editDriverLastName" class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="editDriverLastName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editDriverContact" class="form-label">Contact</label>
                            <input type="text" name="contact" id="editDriverContact" class="form-control"
                                maxlength="11" minlength="11" required pattern="^[0-9]{11}$"
                                title="Contact must be 11 digits only (e.g., 09123456789)">
                        </div>
                        <div class="col-md-6">
                            <label for="editDriverAddress" class="form-label">Address</label>
                            <select name="address" id="editDriverAddress" class="form-select" required>
                                <option value="">Select Barangay</option>
                                <?php foreach ($barangays as $bgy): ?>
                                    <option value="<?= htmlspecialchars($bgy); ?>">
                                        <?= htmlspecialchars($bgy); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editDriverGender" class="form-label">Gender</label>
                            <select name="gender" id="editDriverGender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editDriverAge" class="form-label">Age</label>
                            <input type="number" name="age" id="editDriverAge" class="form-control" required min="18" max="100">
                        </div>
                        <!-- <div class="col-md-6">
                            <label for="editDriverBirthDate" class="form-label">Birth Date</label>
                            <input type="date" name="birth_date" id="editDriverBirthDate" class="form-control" required>
                        </div> -->
                        <div class="col-md-6">
                            <label for="editDriverLicenseNo" class="form-label">License No.</label>
                            <div class="input-group">
                                <input type="text" name="license_no" id="editDriverLicenseNo" class="form-control" required>
                                <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y me-2 p-0 border-0 bg-transparent toggle-password" data-target="editDriverLicenseNo">👁️‍🗨️</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="editDriverPassword" class="form-label">Password</label>
                            <div class="position-relative">
                                <input type="password" name="password" id="editDriverPassword" class="form-control pe-5">
                                <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y me-2 p-0 border-0 bg-transparent toggle-password" data-target="editDriverPassword" tabindex="-1">
                                    👁️‍🗨️
                                </button>
                            </div>
                            <div class="form-text">Leave blank if you don’t want to change password</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer mt-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Driver</button>
                </div>
            </div>
        </form>
    </div>
</div>