<div class="container-fluid">
    <div class="row">
        <?/*<div class="col-sm-3 col-md-2 sidebar">
            <ul class="nav nav-sidebar">
                <li><a href="#">Add</a></li>
                <li><a href="#">Edit</a></li>
                <li><a href="#">Delete</a></li>
            </ul>
        </div>*/?>
        <div class="main">
            <div class="subheader row">
                <h2 class="sub-header pull-left">Leads Management</h2>
                <a class="btn btn-primary pull-right" href="<?=site_url("dashboard/edit_lead/0")?>"><span class="glyphicon glyphicon-plus"></span> Add a new Lead</a>
            </div>

            <div class="subheader row">
                <form id="filters" action="<?=site_url("dashboard/leads")?>" method="GET">
                    <?/*<div class="row">
                        <div class="form-group col-md-6" >
                            <label for="type">Lead Type</label>
                            <select class="form-control" id="type" name="type">
                                <option <?= $type=='' ? 'selected':''?> value="">--</option>
                                <option <?= $type=='quotation_request' ? 'selected':''?> value="quotation_request">quotation request</option>
                                <option <?= $type=='service_request'?'selected':''?> value="service_request">service request</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6" >
                            <label for="status">Lead Status</label>
                            <select class="form-control" id="status" name="status">
                                <option <?= $status=='' ? 'selected':''?> value="">--</option>
                                <option <?= $status=='new' ? 'selected':''?> value="new">new</option>
                                <option <?= $status=='seen'?'selected':''?> value="seen">seen</option>
                                <option <?= $status=='quoted' ? 'selected':''?> value="quoted">quoted</option>
                                <option <?= $status=='won'?'selected':''?> value="won">won</option>
                            </select>
                        </div>
                    </div>*/?>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="startDate">Start date</label>
                            <div class='input-group date datepicker' id="startDate">
                                <input type='text' class="form-control" name="startDate" />
                                <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="endDate">End date</label>
                            <div class='input-group date datepicker' id="endDate">
                                <input type='text' class="form-control" name="endDate" />
                                <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <a class="btn btn-default pull-right" id="filterSubmit">Filter</a>
                        <input type="hidden" name="csv" value="0" />
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Options</th>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Company Name</th>
                        <th>E-mail</th>
                        <th>Generated on</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?/** @var \Ssslim\Libraries\Lead[] $leads */
                    foreach($leads as $s):?>
                    <tr id="contentrow_<?=$s->getLeadId()?>">
                        <td>
                            <a class="pull-left" href="<?=site_url("dashboard/edit_lead/".$s->getLeadId())?>"><span class="btn btn-default  glyphicon glyphicon-pencil"></span></a>
                            <a class="pull-right deletebtn" href="#"  data-contentid="<?=$s->getLeadId()?>"><span class="btn  btn-default glyphicon glyphicon-trash"></span></a>
                        </td>
                        <td><?=$s->getLeadId()?></td>
                        <td><?=$s->getFname()?></td>
                        <td><?=$s->getLname()?></td>
                        <td><?=$s->getCompany()?></td>
                        <td><?=$s->getEmail()?></td>
                        <td><?=$s->getTransdate()?></td>
                    </tr>
                    <?endforeach;?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="row">
        <?=$pagination;?>
    </div>
    <div class="row">
        <a class="btn btn-primary pull-right" id="downloadCSV" href="#"><span class="glyphicon glyphicon-list"></span> Download CSV</a>
    </div>

</div>

<link href="<?= base_url() ?>css/admin/bootstrap-datetimepicker.css" rel="stylesheet">
<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.9.0/moment-with-locales.js"></script>
<script src="<?= base_url() ?>js/admin/bootstrap-datetimepicker.js"></script>


<script type="text/javascript">
    $(function () {
        $('.deletebtn').click(function (e) {
            var content_id=$(this).attr('data-contentid');
            var r = confirm("Do you really wish to delete this content?");
            if (r == true) {
                $.ajax({
                    dataType: "json",
                    url : '<?=site_url("dashboard/deleteLead")?>/'+content_id,
                    type: 'GET',
                    success : function(r){
                        $("#contentrow_"+ r.content_id).remove();
                    }
                })
            }
            return false;
        });

        $('#startDate').datetimepicker({format: 'YYYY-MM-DD', defaultDate:'<?= $startDate?>'});
        $('#endDate').datetimepicker({format: 'YYYY-MM-DD', defaultDate:'<?= $endDate?>'});

        $('#downloadCSV').click(function() {
            $('#filters input[name="csv"]').val("1");
            $('#filters').submit();
            // console.log("x");
            return false;
        });

        $('#filterSubmit').click(function () {
                $('#filters input[name="csv"]').val("0");
                $('#filters').submit();
            }
        );
    });
</script>
