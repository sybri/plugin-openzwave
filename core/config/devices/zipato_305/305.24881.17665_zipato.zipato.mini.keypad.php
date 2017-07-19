<?php
require_once dirname(__FILE__) . "/../../../../../../core/php/core.inc.php";
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    echo '<div class="alert alert-danger div_alert">';
    echo translate::exec('401 - Accès non autorisé');
    echo '</div>';
    die();
}
?>
<legend>Mémoires du clavier <a class="btn btn-primary btn-xs pull-right" id="bt_refreshZipatoAssist"><i class="fa fa-refresh"></i></a></legend>
<div id="div_configureDeviceAlert"></div>
<div class="alert alert-info">
    Info : <br/>
    - Ce tableau vous permet de visualiser les mémoires occupées sur votre clavier<br/>
    - Pour enregistrer un nouveau code cliquez sur le bouton Vert sur la mémoire désirée et suivez les étapes<br/>
    - Pour vider une mémoire, cliquez sur le bouton rouge<br/>
    - Il est impossible d'enregistrer le même code/badge sur deux mémoires différentes<br/>
    - Il est est impossible (par mesure de sécurité) de lire la valeur d'un code enregistré<br/>
</div>
<table class="table table-condensed table-bordered">
    <thead>
    <tr>
        <th>1</th>
        <th>2</th>
        <th>3</th>
        <th>4</th>
        <th>5</th>
        <th>6</th>
        <th>7</th>
        <th>8</th>
        <th>9</th>
        <th>10</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <?php
        /*
        $eqLogic =  openzwave::byId(init('logical_id'));
        $manufacturer_id = $eqLogic->getConfiguration('manufacturer_id');
        $product_type= $eqLogic->getConfiguration('product_type') ;
        $product_id= $eqLogic->getConfiguration('product_id');
        
         echo 'manufacturer_id:'.$manufacturer_id;
         echo 'product_type:'.$product_type;
         echo 'product_id:'.$product_id;
        */
        
        $data = openzwave::callOpenzwave('/node?node_id='.init('logical_id').'&type=info&info=all');
        $data = $data['result']['instances'][1]['commandClasses'][99]['data'];

        for ($i = 1; $i < 11; $i++) {
            echo '<td>';
            echo '<a class="btn btn-success pull-right btn-xs bt_ziptatoKeypadSaveNewCode" data-position="' . $i . '"><i class="fa fa-floppy-o"></i></a>';
            if (isset($data[$i]) && $data[$i]['val'] != '00000000000000000000') {
                echo '<i class="fa fa-check"></i>';
                echo '<a class="btn btn-danger pull-right btn-xs bt_ziptatoKeypadRemoveCode"  data-position="' . $i . '"><i class="fa fa-times"></i></a>';
            } else {
                echo '<i class="fa fa-times"></i>';
            }
            echo '</td>';
        }
        ?>
    </tr>
    </tbody>
</table>

<script>
    $('#bt_refreshZipatoAssist').on('click',function(){
        $('#md_modal').load('index.php?v=d&plugin=openzwave&modal=device.assistant&id=<?php echo init('id');?>&serverId=<?php echo init('serverId');?>&logical_id=<?php echo init('logical_id');?>');
    });
/*
manufacturer_id:151
product_type:24881
product_id:17665
 */
    $('.bt_ziptatoKeypadRemoveCode').on('click',function(){
        var position = $(this).attr('data-position');
        bootbox.confirm('Etes vous sur de vouloir supprimer ce code ?', function (result) {
            var call = '/ZWaveAPI/Run/devices[<?php echo init('logical_id');?>].UserCode.SetRaw('+position+',[00000000000000000000],1)';
            call='node?node_id=<?php echo init('logical_id');?>&type=setRaw&slot_id='+position+'&value0=00000000000000000000';
            if (result) {
                $.ajax({// fonction permettant de faire de l'ajax
                    type: "POST", // méthode de transmission des données au fichier php
                    url: "plugins/openzwave/core/ajax/openzwave.ajax.php", // url du fichier php
                    data: {
                        action: "callRazberry",
                       
                        call: call,
                    },
                    dataType: 'json',
                    error: function (request, status, error) {
                        handleAjaxError(request, status, error, $('#div_configureDeviceAlert'));
                    },
                    success: function (data) { // si l'appel a bien fonctionné
                        if (data.state != 'ok') {
                            $('#div_configureDeviceAlert').showAlert({message: data.result, level: 'danger'});
                            return;
                        }
                        $('#div_configureDeviceAlert').showAlert({message: 'Code enregistré, merci de réveiller votre clavier pour qu\'il soit pris en compte (touche HOME puis 1 puis Enter) ', level: 'success'});
                        $('#md_modal').load('index.php?v=d&plugin=openzwave&modal=device.assistant&id=<?php echo init('id');?>&serverId=<?php echo init('serverId');?>&logical_id=<?php echo init('logical_id');?>');
                    }
                });
            }
        });
    });


    $('.bt_ziptatoKeypadSaveNewCode').on('click',function(){
        var position = $(this).attr('data-position');
        bootbox.confirm('Allez vers votre clavier et appuyez sur Home et passez votre badge en étant sur d\'entendre le bip (dans le cas d\'un code appuyez sur Home , tapez le code et appuyez sur Enter) . Une fois fait cliquer sur D\'ACCORD !', function (result) {
            $.ajax({// fonction permettant de faire de l'ajax
                type: "POST", // méthode de transmission des données au fichier php
                url: "plugins/openzwave/core/ajax/openzwave.ajax.php", // url du fichier php
                data: {
                    action: "callRazberry",
                   
                    //call: '/ZWaveAPI/Run/devices[<?php echo init('logical_id');?>]',
                    call: '/node?node_id=<?php echo init('logical_id');?>&type=info&info=all'
                },
                dataType: 'json',
                error: function (request, status, error) {
                    handleAjaxError(request, status, error, $('#div_configureDeviceAlert'));
                },
                success: function (data) { // si l'appel a bien fonctionné
                    if (data.state != 'ok') {
                        $('#div_configureDeviceAlert').showAlert({message: data.result, level: 'danger'});
                        return;
                    }
                    var code = data.result.result.instances[1].commandClasses[99].data[0].val;
                    var call = '/ZWaveAPI/Run/devices['+configureDeviceLogicalId+'].UserCode.SetRaw('+position+',['+code+'],1)';
                     call='node?node_id=<?php echo init('logical_id');?>&type=setRaw&slot_id='+position+'&value0='+code+'';
                    $.ajax({// fonction permettant de faire de l'ajax
                        type: "POST", // méthode de transmission des données au fichier php
                        url: "plugins/openzwave/core/ajax/openzwave.ajax.php", // url du fichier php
                        data: {
                            action: "callRazberry",
                           
                            call: call,
                        },
                        dataType: 'json',
                        error: function (request, status, error) {
                            handleAjaxError(request, status, error, $('#div_configureDeviceAlert'));
                        },
                        success: function (data) { // si l'appel a bien fonctionné
                            if (data.state != 'ok') {
                                $('#div_configureDeviceAlert').showAlert({message: data.result, level: 'danger'});
                                return;
                            }
                            $('#div_configureDeviceAlert').showAlert({message: 'Code enregistré, merci de réveiller votre clavier pour qu\'il soit pris en compte (touche HOME puis 1 puis Enter) ', level: 'success'});
                            $('#md_modal').load('index.php?v=d&plugin=openzwave&modal=device.assistant&id=<?php echo init('id');?>&serverId=<?php echo init('serverId');?>&logical_id=<?php echo init('logical_id');?>');
                        }
                    });
                }
            });
        });
    });
</script>