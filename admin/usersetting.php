<?php
$page_title = 'Orlando International Resorts';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Orlando International Resorts</h1>
</div>

<?php
// Display session alerts
display_session_alerts();
?>

<?php  
// Session is already started by header.php
if(!isset($_SESSION["user"]) && !isset($_SESSION["user_id"]))
{
 header("Location: index.php");
 exit();
}

ob_start();
?> 

    
            
			  
                 
                                 
            <?php
						include ('db.php');
						$sql = "SELECT * FROM `login`";
						$re = mysqli_query($con,$sql)
				?>
                
            
                    </div>
                    <!--End Advanced Tables -->
					<div class="panel-body">
                            <button class="btn btn-primary btn" data-toggle="modal" data-target="#myModal1">
															Add New Admin
													</button>
                            <div class="modal fade" id="myModal1" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                            <h4 class="modal-title" id="myModalLabel">Add the User name and Password</h4>
                                        </div>
										<form method="post">
                                        <div class="modal-body">
                                            <div class="form-group">
                                            <label>Add new User name</label>
                                            <input name="newus"  class="form-control" placeholder="Enter User name">
											</div>
										</div>
										<div class="modal-body">
                                            <div class="form-group">
                                            <label>New Password</label>
                                            <input name="newps"  class="form-control" placeholder="Enter Password">
											</div>
                                        </div>
										
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
											
                                           <input type="submit" name="in" value="Add" class="btn btn-primary">
										  </form>
										   
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php
						if(isset($_POST['in']))
						{
							$newus = $_POST['newus'];
							$newps = $_POST['newps'];
							
							$newsql ="Insert into login (usname,pass) values ('$newus','$newps')";
							if(mysqli_query($con,$newsql))
							{
							echo' ';
							
						
							}
						header("Location: usersetting.php");
						}
						?>
						
					<div class="panel-body">
                            
                            <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                            <h4 class="modal-title" id="myModalLabel">Change the User name and Password</h4>
                                        </div>
										<form method="post">
                                        <div class="modal-body">
                                            <div class="form-group">
                                            <label>Change User name</label>
                                            <input name="usname" value="<?php echo $us; ?>" class="form-control" placeholder="Enter User name">
											</div>
										</div>
										<div class="modal-body">
                                            <div class="form-group">
                                            <label>Change Password</label>
                                            <input name="pasd" value="<?php echo $ps; ?>" class="form-control" placeholder="Enter Password">
											</div>
                                        </div>
										
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
											
                                           <input type="submit" name="up" value="Update" class="btn btn-primary">
										  </form>
										   
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
               
                <!-- /. ROW  -->
                <?php 
				if(isset($_POST['up']))
				{
					$usname = $_POST['usname'];
					$passwr = $_POST['pasd'];
					
					$upsql = "UPDATE `login` SET `usname`='$usname',`pass`='$passwr' WHERE id = '$id'";
					if(mysqli_query($con,$upsql))
					{
					echo' ';
					
				
					}
				
				header("Location: usersetting.php");
				
				}
				ob_end_flush();
				
				
				
				
				?>
                                
                  
            
			 <!-- /. PAGE INNER  -->
            </div>
         <!-- /. PAGE WRAPPER  -->
        </div>
     <!-- /. WRAPPER  -->
    <!-- JS Scripts-->
    <!-- jQuery Js -->
    
      <!-- Bootstrap Js -->
    
    <!-- Metis Menu Js -->
    
      <!-- Custom Js -->

<?php include '../includes/admin/footer.php'; ?>