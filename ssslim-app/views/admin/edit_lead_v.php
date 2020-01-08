<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <p>Submitted data contains errors, changes have not been saved.<br>
            Please check the fields in red below.<br>
        <?foreach ($errors as $error) {
            print "- " . $error . "<br>";
        }?>
        </p>
    </div>
<?php endif ?>

<?/** @var Ssslim\Libraries\Lead $lead?> */?>
<form id = "editLead" action="<?=site_url("dashboard/edit_lead/".$lead->getLeadId())?>" method="post" enctype="multipart/form-data">
    <div class="row">
        <div class="form-group col-md-12  <?=isset($errors['generatedTime'])?'has-error':''?> ">
            <label for="transdate">Generated time</label>
            <div class='input-group date datepicker' id="generatedTime">
                <input type='text' class="form-control" name="transdate" />
                <span class="input-group-addon">
                    <span class="glyphicon glyphicon-calendar"></span>
                </span>
            </div>
        </div>
    </div>

    <fieldset name="contact">
        <legend>Contact information</legend>
        <div class="row">
            <div class="form-group col-md-4  <?=isset($errors['$this->fname'])?'has-error':''?> ">
                <label for="fname">First Name</label>
                <input type="text" class="form-control" id="fname" name="fname" placeholder="First Name" value="<?= $lead->getFname()?>">
            </div>
            <div class="form-group col-md-4  <?=isset($errors['$this->lname'])?'has-error':''?> ">
                <label for="lname">Last Name</label>
                <input type="text" class="form-control" id="lname" name="lname" placeholder="Last Name" value="<?= $lead->getLname()?>">
            </div>
            <div class="form-group col-md-4  <?=isset($errors['email'])?'has-error':''?> ">
                <label for="email">Email</label>
                <input type="text" class="form-control" id="email" name="email" placeholder="Email" value="<?= $lead->getEmail()?>">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6  <?=isset($errors['$this->jobtitle'])?'has-error':''?> ">
                <label for="jobtitle">Job Title</label>
                <input type="text" class="form-control" id="jobtitle" name="jobtitle" placeholder="Job Title" value="<?= $lead->getJobtitle()?>">
            </div>
            <div class="form-group col-md-6  <?=isset($errors['company'])?'has-error':''?> ">
                <label for="company">Company</label>
                <input type="text" class="form-control" id="company" name="company" placeholder="Company" value="<?= $lead->getCompany()?>">
            </div>
        </div>

        <div class="row">
            <div class="form-group col-md-6  <?=isset($errors['State'])?'has-error':''?> ">
                <label for="state">State</label>
                <input type="text" class="form-control" id="state" name="state" placeholder="State" value="<?= $lead->getState()?>">
            </div>
            <div class="form-group col-md-6  <?=isset($errors['$this->country'])?'has-error':''?> ">
                <label for="country">Country</label>
                <input type="text" class="form-control" id="country" name="country" placeholder="Country" value="<?= $lead->getCountry()?>">
            </div>
        </div>

    </fieldset>

    <div class="row saveBlock">
        <div class="form-group col-md-6">
            <button type="submit" value="doSubmit" name="doSubmit"  class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Save</button>
            <input type="hidden" name="sendQuoteOnLoad" value="0" />
        </div>
    </div>
</form>

<link href="<?= base_url() ?>css/admin/bootstrap-datetimepicker.css" rel="stylesheet">
<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.9.0/moment-with-locales.js"></script>
<script src="<?= base_url() ?>js/admin/bootstrap-datetimepicker.js"></script>
<script type="text/javascript">



    function setSendQuoteButtonState() {
        if ($('#txt').val() != "" &&  $("#value").val() != "" && $("#email").val() != "") {
            $('#sendEmailQuote').removeClass("disabled");
        }
        else  $('#sendEmailQuote').addClass("disabled");
    }

    function doSendEmail() {

        $('#sendEmailQuote').addClass("disabled");
        window.setTimeout(function() {$('#sendEmailQuote').removeClass("disabled")}, 5000);

        $.ajax({
            // dataType: "json",
            url : '<?=site_url("admin/send_email_quote")?>/<?=$lead->getLeadId();?>',
            type: 'GET',
            success : function(r){
                if (r.s !== 0) {
                    alert ('Error sending email: ' + r.e);
                }
                else alert('Quote successfully sent');
            }
        });
    }

    $(function () {
        var sendOnLoad = <?=$sendQuoteOnLoad?>;
        $('#editLead input[name="sendQuoteOnLoad"]').val("0");
        $('#generatedTime').datetimepicker({format: 'YYYY-MM-DD HH:mm', defaultDate: '<?= $lead->getTransdate()?>'});

        setSendQuoteButtonState();

        $('#txt, #value, #email').on('change', setSendQuoteButtonState);

        if (sendOnLoad) doSendEmail();

        $('#sendEmailQuote').click(function () {

            if ($(this).hasClass("disabled")) return;

            if ($('#attachment').val() == "" && $('#downloadAttachment').length == 0) {
                if (!confirm("No quote has been attached. Do you want to send anyway?")) return;
            }

            $('#editLead input[name="sendQuoteOnLoad"]').val("1");
            $('#editLead').submit();
            return false;
        });

        $('#markWon').click(function () {
            //$('#status option[value="won"]').prop("selected", true);
            $('#status').val("won");
            $('#editLead').submit();
        });


    });
</script>