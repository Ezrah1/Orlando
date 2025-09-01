<?php
$page_title = 'Marketing Campaigns';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>
<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Marketing Campaigns</h1>
    <p class="page-subtitle">Campaign Management & Analytics</p>
</div>

<?php
// Display session alerts
display_session_alerts();
?>
                
                <!-- Campaign Metrics -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="panel panel-primary text-center no-boder bg-color-blue">
                            <div class="panel-body">
                                <i class="fa fa-bullhorn fa-5x"></i>
                                <h3>5</h3>
                            </div>
                            <div class="panel-footer back-footer-blue">
                                Active Campaigns
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-primary text-center no-boder bg-color-green">
                            <div class="panel-body">
                                <i class="fa fa-eye fa-5x"></i>
                                <h3>2,450</h3>
                            </div>
                            <div class="panel-footer back-footer-green">
                                Total Impressions
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-primary text-center no-boder bg-color-orange">
                            <div class="panel-body">
                                <i class="fa fa-mouse-pointer fa-5x"></i>
                                <h3>156</h3>
                            </div>
                            <div class="panel-footer back-footer-orange">
                                Click-throughs
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-primary text-center no-boder bg-color-red">
                            <div class="panel-body">
                                <i class="fa fa-money fa-5x"></i>
                                <h3>KES 45K</h3>
                            </div>
                            <div class="panel-footer back-footer-red">
                                Revenue Generated
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Campaigns List -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3>Active Campaigns</h3>
                                <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#newCampaignModal">
                                    <i class="fa fa-plus"></i> New Campaign
                                </button>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Campaign Name</th>
                                                <th>Type</th>
                                                <th>Target Audience</th>
                                                <th>Budget (KES)</th>
                                                <th>Spent (KES)</th>
                                                <th>Impressions</th>
                                                <th>CTR</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Weekend Getaway</td>
                                                <td>Social Media</td>
                                                <td>Local Families</td>
                                                <td>25,000.00</td>
                                                <td>18,500.00</td>
                                                <td>1,200</td>
                                                <td>6.8%</td>
                                                <td><span class="label label-success">Active</span></td>
                                                <td>
                                                    <a href="#" class="btn btn-sm btn-info">View</a>
                                                    <a href="#" class="btn btn-sm btn-warning">Edit</a>
                                                    <a href="#" class="btn btn-sm btn-danger">Pause</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Business Travel</td>
                                                <td>Email</td>
                                                <td>Corporate Clients</td>
                                                <td>15,000.00</td>
                                                <td>12,000.00</td>
                                                <td>800</td>
                                                <td>8.2%</td>
                                                <td><span class="label label-success">Active</span></td>
                                                <td>
                                                    <a href="#" class="btn btn-sm btn-info">View</a>
                                                    <a href="#" class="btn btn-sm btn-warning">Edit</a>
                                                    <a href="#" class="btn btn-sm btn-danger">Pause</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Holiday Special</td>
                                                <td>Google Ads</td>
                                                <td>Tourists</td>
                                                <td>30,000.00</td>
                                                <td>22,000.00</td>
                                                <td>450</td>
                                                <td>4.5%</td>
                                                <td><span class="label label-warning">Paused</span></td>
                                                <td>
                                                    <a href="#" class="btn btn-sm btn-info">View</a>
                                                    <a href="#" class="btn btn-sm btn-warning">Edit</a>
                                                    <a href="#" class="btn btn-sm btn-success">Resume</a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- AI Insights -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3>AI Campaign Suggestions</h3>
                            </div>
                            <div class="panel-body">
                                <div class="alert alert-info">
                                    <strong>Optimization Tip:</strong> Your "Weekend Getaway" campaign performs 23% better on weekends. Consider increasing budget allocation for Friday-Sunday.
                                </div>
                                <div class="alert alert-success">
                                    <strong>Opportunity:</strong> High engagement from business travelers aged 30-45. Consider creating a targeted "Business Plus" package.
                                </div>
                                <div class="alert alert-warning">
                                    <strong>Warning:</strong> "Holiday Special" campaign CTR dropped 15% this week. Consider refreshing ad creatives.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3>Performance Trends</h3>
                            </div>
                            <div class="panel-body">
                                <canvas id="campaignChart" width="400" height="200"></canvas>
                                <p class="text-muted">Last 30 days performance across all campaigns</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Campaign Modal -->
    <div class="modal fade" id="newCampaignModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Create New Campaign</h4>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Campaign Name</label>
                                    <input type="text" class="form-control" placeholder="Enter campaign name">
                                </div>
                                <div class="form-group">
                                    <label>Campaign Type</label>
                                    <select class="form-control">
                                        <option>Social Media</option>
                                        <option>Email Marketing</option>
                                        <option>Google Ads</option>
                                        <option>Facebook Ads</option>
                                        <option>Instagram Ads</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Target Audience</label>
                                    <select class="form-control">
                                        <option>Local Families</option>
                                        <option>Business Travelers</option>
                                        <option>Tourists</option>
                                        <option>Young Professionals</option>
                                        <option>Luxury Travelers</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Budget (KES)</label>
                                    <input type="number" class="form-control" placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>End Date</label>
                                    <input type="date" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Campaign Description</label>
                            <textarea class="form-control" rows="3" placeholder="Describe your campaign goals and strategy"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Create Campaign</button>
                </div>
            </div>
        </div>
    </div>
<?php include '../includes/admin/footer.php'; ?>
