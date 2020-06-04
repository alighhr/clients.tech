
<div class="container" ng-controller="PageController"> 
 <div id="logo" class="text-center"><img src="<?php echo API_URL;?>asset/img/emailer/logo2.png"></div> 
  <!-- Form -->
  <div class="col-12 col-sm-11 col-md-8 col-lg-6 col-xl-5 login-block">
    <h1 class="h3">Admin Sign in</h1>
    <br>
    <p>Please enter your credentials.</p>       
    <br>
    <form method="post" id="login_form" name="login_form"  autocomplete='off'>
      <div class="form-group">
        <input type="text" name="Username" class="form-control form-control-lg" placeholder="Username"  autofocus="">
      </div>

      <div class="form-group">
        <input type="password" name="Password" class="form-control form-control-lg" placeholder="Password">
      </div>

      <div class="form-group">
        <button type="submit" name="submit_button" class="btn btn-success btn-sm" ng-disabled="processing" ng-click="signIn()">Sign in</button>
        <span class="float-right"><a href="recovery" class="a">Forgot password?</a></span>
      </div>
    </form>
  </div>

  <div class="modal fade otp_modal" id="myModal" data-backdrop="static" data-keyboard="false" role="dialog">
    <div class="modal-dialog">  
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">OTP Verification</h5> 
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body p-5">
        <p class="mb-3">Enter your OTP below sent to your registered Mobile no.</p>  
        
          <form id="verifyOTP" ng-submit=" signinwithotp(verifyOTP)" autocomplete="off" class="form_commen " name="verifyOTP"  novalidate="true">
          
          <div class="input-group ">
                <input placeholder="OTP" name="MobileOTP" ng-model="MobileOTP" autocomplete="off" numbers-only class="form-control" type="number" ng-required="true" >
                <div style="color:red" ng-show="btn && verifyOTP.MobileOTP.$error.required" class="form-error">
                    *OTP is required.
                </div>
                <!-- <label>Enter your OTP below sent to your registered Mobile no.</label> -->
                <button class="btn btn-success"> Verify OTP</button>
            </div>
            <form>
          </div>
      </div>
    </div>
  </div>

</div><!-- / container -->