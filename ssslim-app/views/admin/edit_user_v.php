<form action="<?=site_url('admin/edit_user/'.$user->user_id)?>" method="post">

    <div class="row">
        <div class="form-group col-md-5 <?=isset($errors['first_name'])?'has-error':''?> ">
            <label for="first_name">First name</label>
            <input type="text" class="form-control" id="first_name" placeholder="John" name="first_name" value="<?=$user->first_name?>">
        </div>
        <div class="form-group col-md-5 <?=isset($errors['last_name'])?'has-error':''?> ">
            <label for="last_name">Last name</label>
            <input type="text" class="form-control" id="last_name" placeholder="Doe" name="last_name" value="<?=$user->last_name?>">
        </div>
        <div class="form-group col-md-2 <?=isset($errors['title'])?'has-error':''?> ">
            <label for="title">Job Title</label>
            <input type="text" class="form-control" id="title" placeholder="Sir" name="title" value="<?=$user->title?>">
        </div>
    </div>

    <div class="row">
        <div class="form-group col-md-7 <?=isset($errors['organization'])?'has-error':''?> ">
            <label for="organization">Organization</label>
            <input type="text" class="form-control" id="organization" placeholder="Huge Company Ltd." name="organization" value="<?=$user->organization?>">
        </div>
        <div class="form-group col-md-5 <?=isset($errors['country'])?'has-error':''?> ">
            <label for="event_type">Country</label>
            <select class="form-control" id="event_type" name="country">
                <?foreach($countries as $id=>$c):?>
                    <option <?=$user->country==$id?'selected':''?> value="<?=$id?>"><?=$c?></option>
                <?endforeach?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-6 <?=isset($errors['phone_number'])?'has-error':''?> ">
            <label for="phone_number">Phone number</label>
            <input type="text" class="form-control" id="phone_number" placeholder="+1 123 456789" name="phone_number" value="<?=$user->phone_number?>">
        </div>

        <div class="form-group col-md-6 <?=isset($errors['mobile_number'])?'has-error':''?> ">
            <label for="mobile_number">Mobile phone</label>
            <input type="text" class="form-control" id="mobile_number" placeholder="+1 123 456789" name="mobile_number" value="<?=$user->mobile_number?>">
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-9 <?=isset($errors['email'])?'has-error':''?> ">
            <label for="email">E-mail</label>
            <input type="email" class="form-control" id="email" placeholder="name@domain.com" name="email" value="<?=$user->email?>">
        </div>
        <div class="form-group col-md-3 <?=isset($errors['active'])?'has-error':''?> ">
            <label for="active">Status</label>
            <select class="form-control" id="active" name="active">
                <?$us=$user->getHumanAdminStates();foreach($us as $k=>$v):?>
                    <option <?= $user->active==$k?'selected':''?> value="<?=$k?>"><?=$v?></option>
                <?endforeach;?>
            </select>
        </div>
    </div>

    <div class="row saveBlock">
        <div class="form-group col-md-6">
            <button type="submit" value="submit" name="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
    
</form>

<link href="<?= base_url() ?>css/admin/bootstrap-datetimepicker.css" rel="stylesheet">
<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.9.0/moment-with-locales.js"></script>
<script src="<?= base_url() ?>js/admin/bootstrap-datetimepicker.js"></script>
<script type="text/javascript">
    $(function () {
        $('.datepicker').datetimepicker();
    });
</script>