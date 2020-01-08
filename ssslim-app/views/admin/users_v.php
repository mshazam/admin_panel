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
                <h2 class="sub-header pull-left">User Management</h2>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Options</th>
                        <th>#</th>
                        <th>Status</th>
                        <th>First name</th>
                        <th>Last name</th>
                        <th>email</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?foreach($users as $u):?>
                    <tr id="contentrow_<?=$u->user_id?>">
                        <td>
                            <a class="pull-left" href="<?=site_url("dashboard/edit_user/".$u->user_id)?>"><span class="btn btn-default  glyphicon glyphicon-pencil"></span></a>
                            <a class="pull-right deletebtn" href="#"  data-contentid="<?=$u->user_id?>"><span class="btn  btn-default glyphicon glyphicon-trash"></span></a>
                        </td>
                        <td><?=$u->user_id?></td>
                        <td><span id="userstatus_<?=$u->user_id?>"><?=$u->getHumanStatus()?></span><br>
                            <button class='activatebtn btn btn-success btn-xs' style="<?=($u->active==Ssslim\Libraries\User\UserFactory::USER_LEVEL_INACTIVE || $u->active==Ssslim\Libraries\User\UserFactory::USER_LEVEL_BANNED)?'':'display:none'?>" id='activatebtn_<?=$u->user_id?>' data-contentid="<?=$u->user_id?>" href="#">Activate</button>
                            <a class='banbtn btn btn-danger btn-xs' style="<?=($u->active==Ssslim\Libraries\User\UserFactory::USER_LEVEL_ACTIVE || $u->active==Ssslim\Libraries\User\UserFactory::USER_LEVEL_INACTIVE)?'':'display:none'?>"  id='banbtn_<?=$u->user_id?>' data-contentid="<?=$u->user_id?>" href="#">Ban</button>
                        </td>
                        <td><?=$u->first_name?></td>
                        <td><?=$u->last_name?></td>
                        <td><?=$u->email?></td>
                    </tr>
                    <?endforeach?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $('.deletebtn').click(function (e) {
            var content_id=$(this).attr('data-contentid');
            var r = confirm("Do you really wish to delete this user?");
            if (r == true) {
                $.ajax({
                    dataType: "json",
                    url : '<?=site_url("dashboard/userAction/delete")?>/'+content_id,
                    type: 'GET',
                    success : function(r){
                        $("#contentrow_"+ r.content_id).remove();
                    }
                })
            }
            return false;
        });

        $('.activatebtn').click(function (e) {
            var content_id=$(this).attr('data-contentid');
            var r = confirm("Do you really wish to activate this user?");
            if (r == true) {
                $.ajax({
                    dataType: "json",
                    url : '<?=site_url("dashboard/userAction/activate")?>/'+content_id,
                    type: 'GET',
                    success : function(r){
                        if(r.s==1) {
                            $("#activatebtn_" + r.content_id).hide();
                            $("#banbtn_" + r.content_id).show();
                            $("#userstatus_" + r.content_id).text('<?=Ssslim\Libraries\User\User::getHumanAdminStates()[Ssslim\Libraries\User\UserFactory::USER_LEVEL_ACTIVE]?>');
                        }else{
                            alert(r.error);
                        }
                    }
                })
            }
            return false;
        });

        $('.banbtn').click(function (e) {
            var content_id=$(this).attr('data-contentid');
            var r = confirm("Do you really wish to ban this user?");
            if (r == true) {
                $.ajax({
                    dataType: "json",
                    url : '<?=site_url("dashboard/userAction/ban")?>/'+content_id,
                    type: 'GET',
                    success : function(r){
                        $("#banbtn_"+ r.content_id).hide();
                        $("#activatebtn_"+ r.content_id).show();
                        $("#userstatus_"+ r.content_id).text('<?=Ssslim\Libraries\User\User::getHumanAdminStates()[Ssslim\Libraries\User\UserFactory::USER_LEVEL_BANNED]?>');
                    }
                })
            }
            return false;
        });
    });
</script>