
@extends('layout.headerFooter')
@section('content')
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content">
    <div class="col-lg-11">
        <div class="card card-default settings">
        <div class="card-header">
          <h2 class="mb-5">Settings</h2>
        </div>
        @if(session('message'))
        @endif
        <form  method="POST" enctype="multipart/form-data"  action="{{ route('settings.update')}}" autocomplete="off">
          @csrf
          @method('PUT')
          <div class="card-body">

          <div class="form-group row mb-3">
            <label for="address" class="col-sm-4 col-form-label">MAIL MAILER</label>
            <div class="col-sm-8">
              <input type="text" id="MAIL_MAILER" class="form-control" name="MAIL_MAILER" value="<?php echo getSetting('MAIL_MAILER') ?>" placeholder="ENTER MAIL MAILER">
              @error('MAIL_MAILER')
              <p style="color: red">{{ $message }}</p>
              @enderror
            </div>
          </div> 

          <!-- SMTP Address -->
          <div class="form-group row mb-3">
            <label for="address" class="col-sm-4 col-form-label">MAIL HOST NAME</label>
            <div class="col-sm-8">
              <input type="text" id="MAIL_HOST" class="form-control" name="MAIL_HOST" value="<?php echo getSetting('MAIL_HOST') ?>" placeholder="ENTER MAIL HOST NAME">
              @error('MAIL_HOST')
              <p style="color: red">{{ $message }}</p>
              @enderror
            </div>
          </div>
          <div class="form-group row mb-3">
            <label for="address" class="col-sm-4 col-form-label">MAIL PORT</label>
            <div class="col-sm-8">
              <input type="text" id="MAIL_PORT" class="form-control" name="MAIL_PORT" value="<?php echo getSetting('MAIL_PORT') ?>" placeholder="ENTER MAIL PORT">
              @error('MAIL_PORT')
              <p style="color: red">{{ $message }}</p>
              @enderror
            </div>
          </div>
          <div class="form-group row mb-3">
            <label for="address" class="col-sm-4 col-form-label">MAIL USER NAME</label>
            <div class="col-sm-8">
              <input type="text" id="MAIL_USERNAME" class="form-control" name="MAIL_USERNAME" value="<?php echo getSetting('MAIL_USERNAME') ?>" placeholder="ENTER MAIL USER NAME">
              @error('MAIL_USERNAME')
              <p style="color: red">{{ $message }}</p>
              @enderror
            </div>
          </div>
          <div class="form-group row mb-3">
            <label for="address" class="col-sm-4 col-form-label">MAIL FROM ADDRESS</label>
            <div class="col-sm-8">
              <input type="text" id="MAIL_FROM_ADDRESS" class="form-control" name="MAIL_FROM_ADDRESS" value="<?php echo getSetting('MAIL_FROM_ADDRESS') ?>" placeholder="ENTER MAIL FROM ADDRESS">
              @error('MAIL_FROM_ADDRESS')
              <p style="color: red">{{ $message }}</p>
              @enderror
            </div>
          </div>
          <div class="form-group row mb-3">
            <label for="address" class="col-sm-4 col-form-label">MAIL FROM NAME</label>
            <div class="col-sm-8">
              <input type="text" id="MAIL_FROM_NAME" class="form-control" name="MAIL_FROM_NAME" value="<?php echo getSetting('MAIL_FROM_NAME') ?>" placeholder="ENTER MAIL FROM NAME">
              @error('MAIL_FROM_NAME')
              <p style="color: red">{{ $message }}</p>
              @enderror
            </div>
          </div>
          <div class="form-group row mb-3">
            <label for="address" class="col-sm-4 col-form-label">MAIL PASSWORD</label>
            <div class="col-sm-8">
              <input type="text" id="MAIL_PASSWORD" class="form-control" name="MAIL_PASSWORD" value="<?php echo getSetting('MAIL_PASSWORD') ?>" placeholder="ENTER MAIL PASSWORD">
              @error('MAIL_PASSWORD')
              <p style="color: red">{{ $message }}</p>
              @enderror
            </div>
          </div>
          <!-- TWILIO -->
          <div class="form-group row mb-3">
            <label for="address" class="col-sm-4 col-form-label">TWILIO SID</label>
            <div class="col-sm-8">
              <input type="text" id="TWILIO_SID" class="form-control" name="TWILIO_SID" value="<?php echo getSetting('TWILIO_SID') ?>" placeholder="ENTER TWILIO SID">
              @error('TWILIO_SID')
              <p style="color: red">{{ $message }}</p>
              @enderror
            </div>
          </div>
          <div class="form-group row mb-3">
            <label for="address" class="col-sm-4 col-form-label">TWILIO TOKEN</label>
            <div class="col-sm-8">
              <input type="text" id="TWILIO_TOKEN" class="form-control" name="TWILIO_TOKEN" value="<?php echo getSetting('TWILIO_TOKEN') ?>" placeholder="ENTER TWILIO TOKEN">
              @error('TWILIO_TOKEN')
              <p style="color: red">{{ $message }}</p>
              @enderror
            </div>
          </div>
          <div class="form-group row mb-3">
            <label for="address" class="col-sm-4 col-form-label">TWILIO PHONE NO.</label>
            <div class="col-sm-8">
              <input type="text" id="TWILIO_PHONE_NO" class="form-control" name="TWILIO_PHONE_NO" value="<?php echo getSetting('TWILIO_PHONE_NO') ?>" placeholder="ENTER TWILIO PHONE NO">
              @error('TWILIO_PHONE_NO')
              <p style="color: red">{{ $message }}</p>
              @enderror
            </div>
          </div>
          <div class="row">
            <div class="col-12">
            @if(!empty($pre) && $pre->is_modify == 'yes')
             <input type="submit" value="Update Settings" class="btn btn-primary btn-pill">
            @endif
            </div>
          </div>
        </form>
      </div>   
    </div>
    </div>
  </section>
</div>
@endsection

@push('inlinescript')
<script type="text/javascript">
  // add javascript code here
</script>
@endpush