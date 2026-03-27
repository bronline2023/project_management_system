<?php
/**
 * views/admin_wallet_requests.php
 * FINAL VERSION: Fixed Image View Issue (Using Bootstrap Modal & Base64)
 */

if ($_SESSION['user_role'] !== 'admin') { 
    header("Location: ?page=dashboard"); 
    exit; 
}

$pdo = connectDB();

// Fetch only 'pending' requests from database
$sql = "SELECT w.*, u.name, u.email FROM wallet_recharge_requests w JOIN users u ON w.user_id = u.id WHERE w.status = 'pending' ORDER BY w.id ASC";
$pending_requests = fetchAll($pdo, $sql);
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h2 class="mb-0 text-gray-800"><i class="fas fa-money-check-alt text-warning"></i> Pending Wallet Recharges</h2>
    </div>

    <?php if(isset($_SESSION['status_message'])) { echo $_SESSION['status_message']; unset($_SESSION['status_message']); } ?>

    <div class="card shadow-sm border-left-warning mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>User Details</th>
                            <th>Amount</th>
                            <th>Screenshot Proof</th>
                            <th>Request Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($pending_requests)): ?>
                            <tr><td colspan="6" class="p-5 text-muted fs-5">No new recharge request pending. 🎉</td></tr>
                        <?php else: foreach($pending_requests as $req): ?>
                            <tr>
                                <td><span class="badge bg-secondary">#<?= $req['id'] ?></span></td>
                                <td class="fw-bold text-primary text-start">
                                    <?= htmlspecialchars($req['name']) ?><br>
                                    <small class="text-muted"><i class="fas fa-envelope"></i> <?= htmlspecialchars($req['email']) ?></small>
                                </td>
                                <td class="fw-bold text-success fs-5">₹<?= number_format($req['amount'], 2) ?></td>
                                
                                <td>
                                    <?php 
                                    // 100% correct path to image on server
                                    $img_path = UPLOADS_PATH . 'recharge_proofs/' . $req['screenshot_path'];
                                    
                                    if(!empty($req['screenshot_path']) && file_exists($img_path)): 
                                        // Convert image to code instead of URL to avoid dashboard error
                                        $mime = function_exists('mime_content_type') ? mime_content_type($img_path) : 'image/jpeg';
                                        $b64 = base64_encode(file_get_contents($img_path));
                                        $src = "data:$mime;base64,$b64";
                                    ?>
                                        <button type="button" class="btn btn-sm btn-outline-info fw-bold" data-bs-toggle="modal" data-bs-target="#proofModal<?= $req['id'] ?>">
                                            <i class="fas fa-image"></i> View Proof
                                        </button>

                                        <div class="modal fade" id="proofModal<?= $req['id'] ?>" tabindex="-1" aria-hidden="true">
                                          <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content">
                                              <div class="modal-header bg-dark text-white">
                                                <h5 class="modal-title"><i class="fas fa-receipt text-warning"></i> Payment Proof - <?= htmlspecialchars($req['name']) ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                              </div>
                                              <div class="modal-body text-center bg-light">
                                                <img src="<?= $src ?>" class="img-fluid rounded shadow" alt="Payment Proof" style="max-height: 70vh;">
                                              </div>
                                              <div class="modal-footer justify-content-center">
                                                  <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                                              </div>
                                            </div>
                                          </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-danger small fw-bold"><i class="fas fa-exclamation-triangle"></i> File Not Found</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="fw-bold"><?= date('d M, Y', strtotime($req['created_at'])) ?></span><br>
                                    <small class="text-muted"><?= date('h:i A', strtotime($req['created_at'])) ?></small>
                                </td>
                                <td>
                                    <form action="index.php" method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="process_recharge_request">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <button type="submit" name="status" value="approved" class="btn btn-success btn-sm fw-bold px-3 mb-1" onclick="return confirm('Are you sure you want to approve this request? Money will be deposited in the user\'s wallet.');"><i class="fas fa-check"></i> Approve</button><br>
                                        <button type="submit" name="status" value="rejected" class="btn btn-danger btn-sm fw-bold px-3" onclick="return confirm('Are you sure you want to reject this request?');"><i class="fas fa-times"></i> Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>