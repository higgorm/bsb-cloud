<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: text/html; charset=utf-8');
try {
    $hostname = "cpro39746.publiccloud.com.br";
    $dbname = "BDGE_572";
    $username = "sa";
    $pw = "G6T4X9R7";
    $pdo = new PDO ("mssql:host=$hostname;dbname=$dbname","$username","$pw");
  } catch (PDOException $e) {
    echo "Erro de Conexão " . $e->getMessage() . "\n";
    exit;
  }
      $query = $pdo->prepare('select Valor   = sum( case when IsNull( P.VL_TOTAL_BRUTO, 0 ) = 0 then 0 else
       (pm.VL_TOTAL_LIQUIDO+pm.VL_DESCONTO_MERC)  * ( p.VL_TOTAL_LIQUIDO / P.VL_TOTAL_BRUTO ) end )
       from tb_pedido p
       inner join tb_pedido_mercadoria pm on p.cd_loja = pm.cd_loja and p.nr_pedido = pm.nr_pedido
       inner join tb_mercadoria m on pm.cd_mercadoria = m.cd_mercadoria
       where p.cd_loja = "1" and
       p.st_pedido = "F" and
       p.cd_tipo_pedido in ( 1, 2, 5, 10 ) and
       p.DT_PEDIDO BETWEEN GETDATE() -1  and GETDATE()');
      $query->execute();

      while ($row = $query->fetch(PDO::FETCH_ASSOC))
      {
        echo $row[Valor];
      }

?>
