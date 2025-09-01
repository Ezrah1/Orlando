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
?> 

    
            
			  
                 <!-- /. ROW  -->
				 
				 
            
                    </div>
                    <!--End Advanced Tables -->
                </div>
            </div>
                <!-- /. ROW  -->
            
                </div>
               
            </div>
        
               
    </div>
             <!-- /. PAGE INNER  -->
            </div>
         <!-- /. PAGE WRAPPER  -->
     <!-- /. WRAPPER  -->
    <!-- JS Scripts-->
    <!-- jQuery Js -->
    
      <!-- Bootstrap Js -->
    
    <!-- Metis Menu Js -->
    
     <!-- DATA TABLE SCRIPTS -->
    
    
        
         <!-- Custom Js -->

<?php include '../includes/admin/footer.php'; ?>