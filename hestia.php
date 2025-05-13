<?php
/* HestiaCP WHMCS Module 
   https://github.com/JosephChuks/HestiaCP-WHMCS-Module
   Modify By lhl77
   https://github.com/lhl77/HestiaCP-WHMCS-Module
*/


function hestia_MetaData()
{
    return array(
        'DisplayName' => 'HestiaCP',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '8083',
        'DefaultSSLPort' => '8083',
        'ServiceSingleSignOnLabel' => 'Login as User',
        'AdminSingleSignOnLabel' => 'Login as Admin'
    );
}



function hestia_ConfigOptions($params)
{
    return [
        'Package Name' => [
            'Type' => 'text',
            'Default' => 'default'
        ],
        'SSH Access' => [
            'Type' => 'yesno',
            'Description' => 'Tick to grant access',
            'Default' => 'no'
        ],
        'Server IP Address' => [
            'Type' => 'text',
        ],
    ];
}


function hestia_AdminCustomButtonArray()
{
    return array(
        "Install LetsEncrypt SSL" => "InstallSsl",
    );
}


function hestia_CreateAccount($params)
{


    if ($params["server"] == 1) {
		if(empty($params['username'])){
			$createName = hestiaNameGetRandomString(7);
		}else{
			$createName = $params['username'];
		}

        $postvars = array(
            'hash' => $params["serveraccesshash"],
            'returncode' => 'yes',
            'cmd' => 'v-add-user',
            // 'arg1' => $params["username"],
            'arg1' => $createName,
            'arg2' => $params["password"],
            'arg3' => $params["clientsdetails"]["email"],
            'arg4' => $params["configoption1"],
            'arg5' => $params["clientsdetails"]["firstname"],
            'arg6' => $params["clientsdetails"]["lastname"],
        );
        $postdata = http_build_query($postvars);


        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $answer = curl_exec($curl);

        logModuleCall('hestia', 'CreateAccount_UserAccount', 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/' . $postdata, $answer);

        /* 保存username */
        Capsule::table('tblhosting')->where('id', $params['serviceid'])->update([
            'username' => $createName,
        ]);

        // Enable ssh access
        if (($answer == 0) && ($params["configoption2"] == 'on')) {
            $postvars = array(
                'hash' => $params["serveraccesshash"],
                'returncode' => 'yes',
                'cmd' => 'v-change-user-shell',
                // 'arg1' => $params["username"],
                'arg1' => $createName,
                'arg2' => 'bash'
            );
            $postdata = http_build_query($postvars);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
            $answer = curl_exec($curl);

            logModuleCall('hestia', 'CreateAccount_EnableSSH', 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/' . $postdata, $answer);
        }

        // Add domain
        if (($answer == 0) && (!empty($params["domain"]))) {
            $postvars = array(
                'hash' => $params["serveraccesshash"],
                'returncode' => 'yes',
                'cmd' => 'v-add-domain',
                // 'arg1' => $params["username"],
                'arg1' => $createName,
                'arg2' => $params["domain"],
                'arg3' => $params["configoption3"],
            );
            $postdata = http_build_query($postvars);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
            $answer = curl_exec($curl);

            logModuleCall('hestia', 'CreateAccount_AddDomain', 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/' . $postdata, $answer);
        }
    }

    if ($answer == 0) {
        $result = "success";
    } else {
        $result = $answer;
    }

    return $result;
}

function hestia_TerminateAccount($params)
{


    if ($params["server"] == 1) {


        $postvars = array(
            'hash' => $params["serveraccesshash"],
            'returncode' => 'yes',
            'cmd' => 'v-delete-user',
            'arg1' => $params["username"]
        );
        $postdata = http_build_query($postvars);

        // Delete user account
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $answer = curl_exec($curl);
    }

    logModuleCall('hestia', 'TerminateAccount', 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/' . $postdata, $answer);

    if ($answer == 0) {
        $result = "success";
    } else {
        $result = $answer;
    }

    return $result;
}

function hestia_SuspendAccount($params)
{


    if ($params["server"] == 1) {


        $postvars = array(
            'hash' => $params["serveraccesshash"],
            'returncode' => 'yes',
            'cmd' => 'v-suspend-user',
            'arg1' => $params["username"]
        );
        $postdata = http_build_query($postvars);

        // Susupend user account
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $answer = curl_exec($curl);
    }

    logModuleCall('hestia', 'SuspendAccount', 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/' . $postdata, $answer);

    if ($answer == 0) {
        $result = "success";
    } else {
        $result = $answer;
    }

    return $result;
}

function hestia_UnsuspendAccount($params)
{


    if ($params["server"] == 1) {


        $postvars = array(
            'hash' => $params["serveraccesshash"],
            'returncode' => 'yes',
            'cmd' => 'v-unsuspend-user',
            'arg1' => $params["username"]
        );
        $postdata = http_build_query($postvars);

        // Unsusupend user account
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $answer = curl_exec($curl);
    }

    logModuleCall('hestia', 'UnsuspendAccount', 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/' . $postdata, $answer);

    if ($answer == 0) {
        $result = "success";
    } else {
        $result = $answer;
    }

    return $result;
}

function hestia_ChangePassword($params)
{


    if ($params["server"] == 1) {


        $postvars = array(
            'hash' => $params["serveraccesshash"],
            'returncode' => 'yes',
            'cmd' => 'v-change-user-password',
            'arg1' => $params["username"],
            'arg2' => $params["password"]
        );
        $postdata = http_build_query($postvars);

        // Change user package
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $answer = curl_exec($curl);
    }

    logModuleCall('hestia', 'ChangePassword', 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/' . $postdata, $answer);

    if ($answer == 0) {
        $result = "success";
    } else {
        $result = $answer;
    }

    return $result;
}

function hestia_ChangePackage($params)
{


    if ($params["server"] == 1) {


        $postvars = array(
            'hash' => $params["serveraccesshash"],
            'returncode' => 'yes',
            'cmd' => 'v-change-user-package',
            'arg1' => $params["username"],
            'arg2' => $params["configoption1"]
        );
        $postdata = http_build_query($postvars);

        // Change user package
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $answer = curl_exec($curl);
    }

    logModuleCall('hestia', 'ChangePackage', 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/' . $postdata, $answer);

    if ($answer == 0) {
        $result = "success";
    } else {
        $result = $answer;
    }

    return $result;
}



function hestia_InstallSsl($params)
{


    if ($params["server"] == 1) {

        $postvars = array(
            'hash' => $params["serveraccesshash"],
            'returncode' => 'yes',
            'cmd' => 'v-add-letsencrypt-domain',
            'arg1' => $params["username"],
            'arg2' => $params["domain"],
            'arg3' => '',
            'arg4' => 'yes',
        );
        $postdata = http_build_query($postvars);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $answer = curl_exec($curl);
    }

    logModuleCall('hestia', 'InstallSSL', 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/' . $postdata, $answer);

    if ($answer == 0) {
        $result = "success";
    } else {
        $result = $answer;
    }

    return $result;
}

// function hestia_ClientArea($params)
// {

//     $code = '
// <form action="https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/login/" method="post" target="_blank">
// <input type="hidden" name="user" value="' . $params["username"] . '" />
// <input type="hidden" name="password" value="' . $params["password"] . '" />
// <input type="hidden" name="api" value="1" />
// <input type="submit" value="Login to Control Panel" />
// </form>';
//     return $code;
// }

function hestia_UsageUpdate($params)
{

    $postvars = array(
        'hash' => $params["serveraccesshash"],
        'returncode' => 'yes',
        'cmd' => 'v-list-users',
        'arg1' => 'json'
    );
    $postdata = http_build_query($postvars);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    $answer = curl_exec($curl);

    $results = json_decode($answer, true);


    foreach ($results as $user => $values) {
        update_query("tblhosting", array(
            "diskusage" => $values['U_DISK'],
            "disklimit" => $values['DISK_QUOTA'],
            "bwusage" => $values['U_BANDWIDTH'],
            "bwlimit" => $values['BANDWIDTH'],
            "lastupdate" => "now()",
        ), array("server" => $params['serverid'], "username" => $user));
    }
}


function hestia_ClientArea($params)
{
    // 获取用户详情和使用信息
    $postvars = array(
        'hash' => $params["serveraccesshash"],
        'returncode' => 'yes',
        'cmd' => 'v-list-user',
        'arg1' => $params["username"],
        'arg2' => 'json'
    );
    $postdata = http_build_query($postvars);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/api/');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    $answer = curl_exec($curl);
    $userDetails = json_decode($answer, true);
    
    // 获取用户域名
    $postvars = array(
        'hash' => $params["serveraccesshash"],
        'returncode' => 'yes',
        'cmd' => 'v-list-web-domains',
        'arg1' => $params["username"],
        'arg2' => 'json'
    );
    $postdata = http_build_query($postvars);
    
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    $answer = curl_exec($curl);
    $domains = json_decode($answer, true);
    curl_close($curl);
    
    // 用于显示的用户数据
    $userData = isset($userDetails[$params["username"]]) ? $userDetails[$params["username"]] : array();
    
    $packageName = $params["configoption1"];
    $status = isset($userData['SUSPENDED']) && $userData['SUSPENDED'] == 'yes' ? '<span class="status status-suspended">已暂停</span>' : '<span class="status status-active">正常</span>';
    
    // 开始构建HTML - 使用WHMCS风格的卡片布局
    $code = '
    <div class="hestia-client-area">
        <div class="row">
            <!-- 账户概览卡片 -->
            <div class="col-md-6 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-circle"></i> 账户概览</h3>
                    </div>
                    <div class="card-body">
                        <div class="account-item">
                            <span class="item-label">用户名</span>
                            <span class="item-value">' . htmlspecialchars($params["username"]) . '</span>
                        </div>
                        <div class="account-item">
                            <span class="item-label">密码</span>
                            <input readonly="readonly" style="color:darkgray;" value="' . htmlspecialchars($params["password"]) . '">
                        </div>
                        <div class="account-item">
                            <span class="item-label">套餐</span>
                            <span class="item-value">' . htmlspecialchars($packageName) . '</span>
                        </div>
                        <div class="account-item">
                            <span class="item-label">状态</span>
                            <span class="item-value">' . $status . '</span>
                        </div>
                        <div class="account-item">
                            <span class="item-label">面板教程</span>
                            <span class="item-value">
                            <a href="https://blog.lhl.one">编写中...</a>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 快捷操作卡片 -->
            <div class="col-md-6 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-tools"></i> 快捷操作</h3>
                    </div>
                    <div class="card-body">
                        <div class="action-buttons">
                            <form action="https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/login/" method="post" target="_blank">
                                <input type="hidden" name="user" value="' . htmlspecialchars($params["username"]) . '" />
                                <input type="hidden" name="password" value="' . htmlspecialchars($params["password"]) . '" />
                                <input type="hidden" name="api" value="1" />
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-sign-in-alt"></i> 登录控制面板
                                </button>
                            </form>';
                            
    // 如果主域名存在，添加网页邮箱和文件管理器快捷链接
    //if (!empty($params["domain"])) {
    //    $code .= '
    //                        <a href="http://' . htmlspecialchars($params["domain"]) . '/webmail" target="_blank" class="btn btn-info btn-block">
      //                          <i class="fas fa-envelope"></i> 网页邮箱
     //                       </a>
     //                       <a href="https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/fm/?module=filemanager" target="_blank" class="btn btn-success btn-block">
      //                          <i class="fas fa-folder-open"></i> 文件管理器
     //                       </a>';
    //}
    
    $code .= '
                        </div>
                    </div>
                </div>
            </div>
        </div>
          ';
        
    // 如果存在域名则显示域名部分
    if (!empty($domains) && is_array($domains)) {
        $code .= '
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-globe"></i> 我的网站</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>域名</th>
                                        <th>IP地址</th>
                                        <th>SSL证书</th>
                                        <th>状态</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>';
                            
        foreach ($domains as $domain => $details) {
            $sslStatus = isset($details['SSL']) && $details['SSL'] != 'no' 
                ? '<span class="ssl-badge ssl-active"><i class="fas fa-lock"></i> 已启用</span>' 
                : '<span class="ssl-badge ssl-inactive"><i class="fas fa-unlock"></i> 未启用</span>';
            
            $domainStatus = isset($details['SUSPENDED']) && $details['SUSPENDED'] == 'yes' 
                ? '<span class="status status-suspended">已暂停</span>' 
                : '<span class="status status-active">正常</span>';
            
            $ipAddress = isset($details['IP']) ? htmlspecialchars($details['IP']) : '-';
            
            $code .= '
                <tr>
                    <td><strong>' . htmlspecialchars($domain) . '</strong></td>
                    <td>' . $ipAddress . '</td>
                    <td>' . $sslStatus . '</td>
                    <td>' . $domainStatus . '</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="http://' . htmlspecialchars($domain) . '" target="_blank" class="btn btn-sm btn-outline-primary" title="访问网站">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            <a href="https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/edit/web/?domain=' . htmlspecialchars($domain) . '" target="_blank" class="btn btn-sm btn-outline-secondary" title="管理网站">
                                <i class="fas fa-cog"></i>
                            </a>
                        </div>
                    </td>
                </tr>';
        }
        
        $code .= '
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    // 添加使用统计信息部分 (如果有数据)
    if (isset($userData['DISK_QUOTA']) && isset($userData['BANDWIDTH'])) {
        $diskQuota = isset($userData['DISK_QUOTA']) ? $userData['DISK_QUOTA'] : '∞';
        $diskUsed = isset($userData['U_DISK']) ? $userData['U_DISK'] : '0';
        $bandwidthQuota = isset($userData['BANDWIDTH']) ? $userData['BANDWIDTH'] : '∞';
        $bandwidthUsed = isset($userData['U_BANDWIDTH']) ? $userData['U_BANDWIDTH'] : '0';
        
        // 计算百分比用于进度条
        $diskPercentage = ($diskQuota != '∞' && $diskQuota > 0) ? min(100, round(($diskUsed / $diskQuota) * 100)) : 0;
        $bandwidthPercentage = ($bandwidthQuota != '∞' && $bandwidthQuota > 0) ? min(100, round(($bandwidthUsed / $bandwidthQuota) * 100)) : 0;
        
        // 根据使用百分比设置进度条颜色
        $diskProgressClass = $diskPercentage < 70 ? 'bg-success' : ($diskPercentage < 90 ? 'bg-warning' : 'bg-danger');
        $bandwidthProgressClass = $bandwidthPercentage < 70 ? 'bg-success' : ($bandwidthPercentage < 90 ? 'bg-warning' : 'bg-danger');
        
        $code .= '
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie"></i> 资源使用情况</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="usage-item">
                                    <div class="usage-label">
                                        <span>磁盘空间</span>
                                        <span class="usage-text">' . $diskUsed . ' / ' . $diskQuota . ' MB</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar ' . $diskProgressClass . '" role="progressbar" style="width: ' . $diskPercentage . '%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="usage-item">
                                    <div class="usage-label">
                                        <span>带宽流量</span>
                                        <span class="usage-text">' . $bandwidthUsed . ' / ' . $bandwidthQuota . ' MB</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar ' . $bandwidthProgressClass . '" role="progressbar" style="width: ' . $bandwidthPercentage . '%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    // 结束 HTML 并添加 CSS 样式
    $code .= '
    </div>
    
    <style>
    .hestia-client-area {
        font-family: var(--body-font, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif);
        margin-bottom: 30px;
    }
    
    .hestia-client-area .row {
        margin-bottom: 20px;
    }
    
    .hestia-client-area .card {
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        border: 1px solid rgba(0,0,0,0.125);
    }
    
    .hestia-client-area .card-header {
        background-color: #f8f9fa;
        padding: 12px 15px;
        border-bottom: 1px solid rgba(0,0,0,0.125);
    }
    
    .hestia-client-area .card-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }
    
    .hestia-client-area .card-header h3 i {
        margin-right: 8px;
        color: #0c73b8;
    }
    
    .hestia-client-area .card-body {
        padding: 15px;
    }
    
    .hestia-client-area .account-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .hestia-client-area .account-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .hestia-client-area .item-label {
        color: #6c757d;
        font-weight: 500;
    }
    
    .hestia-client-area .item-value {
        font-weight: 500;
        text-align: right;
    }
    
    .hestia-client-area .status {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .hestia-client-area .status-active {
        background-color: #e3f7e8;
        color: #28a745;
    }
    
    .hestia-client-area .status-suspended {
        background-color: #fbecec;
        color: #dc3545;
    }
    
    .hestia-client-area .ssl-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 6px;
        border-radius: 3px;
        font-size: 12px;
    }
    
    .hestia-client-area .ssl-active {
        background-color: #e3f7e8;
        color: #28a745;
    }
    
    .hestia-client-area .ssl-inactive {
        background-color: #f0f0f0;
        color: #6c757d;
    }
    
    .hestia-client-area .ssl-badge i {
        margin-right: 4px;
    }
    
    .hestia-client-area .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .hestia-client-area .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
        padding: 8px 12px;
        transition: all 0.2s;
    }
    
    .hestia-client-area .btn i {
        margin-right: 8px;
    }
    
    .hestia-client-area .usage-item {
        margin-bottom: 15px;
    }
    
    .hestia-client-area .usage-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .hestia-client-area .usage-text {
        color: #6c757d;
        font-size: 13px;
    }
    
    .hestia-client-area .progress {
        height: 8px;
        background-color: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .hestia-client-area .table {
        margin-bottom: 0;
    }
    
    .hestia-client-area .table td {
        vertical-align: middle;
    }
    
    /* 响应式调整 */
    @media (max-width: 767.98px) {
        .hestia-client-area .btn-group-sm {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .hestia-client-area .card-body.p-0 {
            padding: 0 !important;
        }
    }
    </style>';
    
    return $code;
}

function hestia_AdminLink($params)
{

    $code = '<form action="https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/login/" method="post" target="_blank">
<input type="hidden" name="user" value="' . $params["serverusername"] . '" />
<input type="hidden" name="password" value="' . $params["serverpassword"] . '" />
<input type="submit" value="Login to Control Panel" />
</form>';
    return $code;
}

function hestia_LoginLink($params)
{

    echo '
    <style>#btnLoginLinkTrigger { display: none }</style>
        <div class="col-sm-5">
        <a href="https://' . $params["serverhostname"] . ':' . $params["serverport"] . '/login/" class="btn btn-primary" target="_blank">
            <i class="fas fa-sign-in fa-fw"></i> Login to Control Panel
        </a>
    </div>
    ';
}

function hestiaNameGetRandomString($len, $chars=null)  
{  
    if (is_null($chars)) {  
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
    }  
    mt_srand(10000000*(double)microtime());  
    for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {  
        $str .= $chars[mt_rand(0, $lc)];  
    }  
    return $str;  
}
