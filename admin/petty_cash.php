<?php
$page_title = 'Petty Cash';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Petty Cash</h1>
</div>

<?php
// Display session alerts
display_session_alerts();
?>

<?php  


?> 

    
            
                
                
                
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Request Modal -->
    <div class="modal fade" id="newRequestModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">New Petty Cash Request</h4>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label>Department</label>
                            <select class="form-control">
                                <option>Rooms</option>
                                <option>Bar</option>
                                <option>Kitchen</option>
                                <option>Housekeeping</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Purpose</label>
                            <textarea class="form-control" rows="3" placeholder="Describe the purpose of this request"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Amount (KES)</label>
                            <input type="number" class="form-control" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Receipt Upload</label>
                            <input type="file" class="form-control">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Submit Request</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JS Scripts-->

<?php include '../includes/admin/footer.php'; ?>