<header class="panel-heading">
    <h1 class="h4"><?php echo $this->ModuleData['ModuleTitle']; ?></h1>
</header>

<div class="panel-body" ng-controller="PageController" ng-init="getList()"><!-- Body -->
    <div class="">
        <div class="wrapper wrapper-content">
            <div class="row mb-3 align-items-stretch">
                
                    <div class="col-xl-3 col-sm-6 py-2">
                        <div class="card">
                            <div class="card-body custom-card-body">
                                <div class="rotate col-3">
                                    <i class="fa fa-user font_icon"></i>
                                </div>
                                <div class="card-info col-9" ng-click="LoadUserList('')">
                                    <h6> Total Clients </h6>
                                    <h4>{{data.dataList.TotalUsers}}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                  
            </div>
        </div>
    </div>

    <hr/>

</div><!-- Body/ -->

