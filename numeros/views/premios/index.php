<?php
/* @var $this yii\web\View */

$this->params['breadcrumbs'] = array ('professor' => $professor, 'updatedAt' => $updatedAt);
?>

 <!-- Este script troca a classe da aba do menu correspondente à página acessada -->
<script>
    var li =document.getElementsByTagName("li");
    for(var i=0;i<li.length;i++) {
        if(li[i].className == "dropdown" || li[i].className == "dropdown active")
            li[i].className = "dropdown";
        else
            li[i].className = "";
    }
    document.getElementById("li-premios").className = "active";
</script>

<div class="container theme-showcase" role="main">
    <div class="panel panel-default">
        <div class="panel-heading clickable panel-collapsed">
            <h4 class="panel-title">
                <a href="#">Prêmios e Títulos (<?= $countPremios ?>)</a>
            </h4>
            <span class="pull-right"><i class="glyphicon glyphicon-minus"></i></span>
        </div>
        <div class="panel-body">
            <?php 
                echo "<ol>";
                foreach ($premios as $premio) {
                    echo "<li><strong>" . $premio['titulo'] . ".</strong> ";
                    echo $premio['entidade'] . ", ";
                    echo $premio['ano'] . ".</li><br>";
                }
                echo "</ol>";
            ?>    
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

<script type="text/javascript">
    $(document).on('click', '.panel-heading span.clickable', function (e) {
        var $this = $(this);
        if (!$this.hasClass('panel-collapsed')) {
            $this.parents('.panel').find('.panel-body').slideUp();
            $this.addClass('panel-collapsed');
            $this.find('i').removeClass('glyphicon-minus').addClass('glyphicon-plus');
        } else {
            $this.parents('.panel').find('.panel-body').slideDown();
            $this.removeClass('panel-collapsed');
            $this.find('i').removeClass('glyphicon-plus').addClass('glyphicon-minus');
        }
    });
    $(document).on('click', '.panel div.clickable', function (e) {
        var $this = $(this);
        if (!$this.hasClass('panel-collapsed')) {
            $this.parents('.panel').find('.panel-body').slideUp();
            $this.addClass('panel-collapsed');
            $this.find('i').removeClass('glyphicon-minus').addClass('glyphicon-plus');
        } else {
            $this.parents('.panel').find('.panel-body').slideDown();
            $this.removeClass('panel-collapsed');
            $this.find('i').removeClass('glyphicon-plus').addClass('glyphicon-minus');
        }
    });
    $(document).ready(function () {
        $('.panel-heading span.clickable').click();
        $('.panel div.clickable').click();
    });
</script>