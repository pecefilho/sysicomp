<?php

namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use app\models\User;
use app\models\Aluno;
use app\models\UserSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\IntegrityException;
use yii\base\Exception;
use app\models\UploadLattesForm;
use app\models\UploadCvsdisciplinasForm;
use app\models\UploadCvsalunosForm;
use yii\web\UploadedFile;
use yii\base\ErrorException;

/**
* UserController implements the CRUD actions for User model.
*/
class UserController extends Controller
{
  /**
  * @inheritdoc
  */
  public function behaviors()
  {
    return [
      'access' => [
        'class' => \yii\filters\AccessControl::className(),
        'rules' => [
          [
            'allow' => true,
            'roles' => ['@'],
            'matchCallback' => function ($rule, $action) {
              return (Yii::$app->user->identity->checarAcesso('administrador') || Yii::$app->user->identity->checarAcesso('secretaria'));
            }
          ],
          [   'actions' => ['perfil', 'lattes', 'uploadDisciplinas'],
          'allow' => true,
          'roles' => ['@'],
        ],
        [ 'actions' => ['update'],
        'allow' => true,
        'roles' => ['@'],
        'matchCallback' => function ($rule, $action) {
          return Yii::$app->user->identity->id == filter_input(INPUT_GET, 'id') ;
        }
      ],
    ],
  ],
  'verbs' => [
    'class' => VerbFilter::className(),
    'actions' => [
      'delete' => ['POST'],
    ],
  ],
];
}

/**
* Lists all User models.
* @return mixed
*/
public function actionIndex()
{
  $searchModel = new UserSearch();
  $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

  return $this->render('index', [
    'searchModel' => $searchModel,
    'dataProvider' => $dataProvider,
  ]);
}

/**
* Displays a single User model.
* @param integer $id
* @return mixed
*/
public function actionView($id)
{
  return $this->render('view', [
    'model' => $this->findModel($id),
  ]);
}

public function actionPerfil()
{
  return $this->render('perfil', [
    'model' => $this->findModel(Yii::$app->user->identity->id),
  ]);
}

/**
* Updates an existing User model.
* If update is successful, the browser will be redirected to the 'view' page.
* @param integer $id
* @return mixed
*/
public function actionUpdate($id)
{
  $model = $this->findModel($id);

  if ($model->load(Yii::$app->request->post())) {
    $model->setPassword();
    if($model->save()){
      $this->mensagens('success', 'Usuário Alterado', 'Usuário alterado com sucesso.');
      return Yii::$app->user->identity->checarAcesso('administrador') ? $this->redirect(['view', 'id' => $model->id]) : $this->redirect(['perfil']);
    }
    else
    $this->mensagens('danger', 'Erro ao Alterar Usuário', 'Ocorreu um erro ao alterar o usuário. Verifique os campos e tente novamente.');
  } else {
    return $this->render('update', [
      'model' => $model,
    ]);
  }
}

/**
* Deletes an existing User model.
* If deletion is successful, the browser will be redirected to the 'index' page.
* @param integer $id
* @return mixed
*/
public function actionDelete($id)
{
  $model = $this->findModel($id);

  if($model->professor){
    $alunos = $model->getAlunos($model->id);
    if($alunos){
      $this->mensagens('warning', 'Usuário com alunos associados', 'O usuário corrente é professor e possui alunos.');
      return $this->redirect(['index']);
    }
  }

  try{
    $model->delete();
    $this->mensagens('success', 'Usuário Removido', 'Usuário removido com sucesso.');
  }catch(IntegrityException $e){
    $this->mensagens('danger', 'Erro ao Remover Usuário', 'Ocorreu um erro ao remover o Usuário.');
  }

  return $this->redirect(['index']);
}

/**
* faz o upload do csv contendo os alunos 
*/
public function actionCvsalunos()
{
  $model = new UploadCvsalunosForm();
  $dir = '';

  if (Yii::$app->request->isPost) {
    $model->csvAlunosFile = UploadedFile::getInstance($model, 'csvAlunosFile');
    if($model->upload()){
      $dir = 'uploads/alunosCsv.csv';
      $handle = fopen($dir, "r");
      fgetcsv($handle);
      fgetcsv($handle);
      while (($fileop = fgetcsv($handle, 0, ";")) !== false)
      {
        $id_pessoa = utf8_encode($fileop[0]);
        $nome_pessoa = utf8_encode($fileop[1]);
        $sexo = utf8_encode($fileop[2]);
        $dt_nascimento = utf8_encode($fileop[3]);
        $dt_nascimento = substr($dt_nascimento, 0, 10);
        $forma_ingresso = utf8_encode($fileop[4]);
        $forma_evasao = utf8_encode($fileop[5]);
        $cod_curso = utf8_encode($fileop[6]);
        $nome_unidade = utf8_encode($fileop[7]);
        $matr_aluno = utf8_encode($fileop[8]);
        $num_versao = utf8_encode($fileop[9]);
        $periodo_ingresso = utf8_encode($fileop[10]);
        $dt_evasao = utf8_encode($fileop[11]);
        $dt_evasao = substr($dt_evasao, 0, 10);
        $periodo_evasao = utf8_encode($fileop[12]);

        $sql = "INSERT INTO j17_aluno_grad VALUES ('$id_pessoa', '$nome_pessoa', '$sexo', '$dt_nascimento', '$forma_ingresso', '$forma_evasao', '$cod_curso', '$nome_unidade', '$matr_aluno', '$num_versao', '$periodo_ingresso', '$dt_evasao', '$periodo_evasao') ON DUPLICATE KEY UPDATE MATR_ALUNO = MATR_ALUNO";
        $query = Yii::$app->db->createCommand($sql)->execute();
      }
      unlink($dir);
      $this->mensagens('success', 'Sucesso', 'Upload foi realizado com sucesso.');
    } else {
      $this->mensagens('danger', 'ERRO', 'UPLOAD csv nao esta correto.');
    }
  }
  return $this->render('cvsalunos', ['model' => $model]);
}


/**
* faz o upload do xml contendo o curriculo lattes
*/
public function actionLattes()
{
  $model = new UploadLattesForm();
  $idUsuario = Yii::$app->user->identity->id;
  $dir = '';

  if (Yii::$app->request->isPost) {
    $model->lattesFile = UploadedFile::getInstance($model, 'lattesFile');
    if ($model->upload($idUsuario)) {
      try {
        // file is uploaded successfully
        $dir = 'uploads/lattes-'.$idUsuario.'.xml';
        $xml = simplexml_load_file($dir);
        $formacao = '';

        $idLattes = $xml['NUMERO-IDENTIFICADOR'];
        if(!empty($xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'DOUTORADO'}) || ($xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'DOUTORADO'}) != null){
          $formacao = "3;" . $xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'DOUTORADO'}['NOME-CURSO'] . ";" . $xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'DOUTORADO'}['NOME-INSTITUICAO'] . ";" . $xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'DOUTORADO'}['ANO-DE-CONCLUSAO'];
        } else if(!empty($xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'MESTRADO'}) || ($xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'MESTRADO'}) != null){
          $formacao = "2;" . $xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'MESTRADO'}['NOME-CURSO'] . ";" . $xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'MESTRADO'}['NOME-INSTITUICAO'] . ";" . $xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'MESTRADO'}['ANO-DE-CONCLUSAO'];
        } else if(!empty($xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'GRADUACAO'}) || ($xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'GRADUACAO'}) != null){
          $formacao = "1;" . $xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'GRADUACAO'}['NOME-CURSO'] . ";" . $xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'GRADUACAO'}['NOME-INSTITUICAO'] . ";" . $xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'}->{'GRADUACAO'}['ANO-DE-CONCLUSAO'];
        }

        $formacao = str_replace("'","",$formacao);
        $resumo = $xml->{'DADOS-GERAIS'}->{'RESUMO-CV'}['TEXTO-RESUMO-CV-RH'];

        $data = $xml['DATA-ATUALIZACAO'];
        $data = \DateTime::createFromFormat('dmY', $data);
        $data = $data->format('d/m/Y');

        $resumo = str_replace("'","",$resumo);

        //popula j17_user
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("UPDATE j17_user SET idLattes=:column1, formacao=:column2, resumo=:column3, updated_at=:column4, ultimaAtualizacao=:column5 WHERE id=:id")
        ->bindValue(':column1', $idLattes)
        ->bindValue(':column2', $formacao)
        ->bindValue(':column3', $resumo)
        ->bindValue(':column4', $data)
        ->bindValue(':column5', date("d/m/Y"))
        ->bindValue(':id', $idUsuario)
        ->execute();

        //popula j17_premios
        foreach ($xml->{'DADOS-GERAIS'}->{'PREMIOS-TITULOS'} as $premio) {
          for ($i=0; $i < count($premio); $i++) {
            $titulo = $premio->{'PREMIO-TITULO'}[$i]['NOME-DO-PREMIO-OU-TITULO'];
            $titulo = str_replace("'","",$titulo);
            $entidade = $premio->{'PREMIO-TITULO'}[$i]['NOME-DA-ENTIDADE-PROMOTORA'];
            $entidade = str_replace("'","",$entidade);
            $ano = $premio->{'PREMIO-TITULO'}[$i]['ANO-DA-PREMIACAO'];

            if($i == 1){
              $titulo0 = $titulo;
            }

            $boolExistePremio = (new \yii\db\Query())
            ->from('j17_premios')
            ->where(['LIKE', 'titulo', $titulo])
            ->count();

            if($boolExistePremio == 0){
              $sql0 = "INSERT INTO j17_premios (idProfessor, titulo, entidade, ano) VALUES ($idUsuario, '$titulo', '$entidade', '$ano');";
              Yii::$app->db->createCommand($sql0)->execute();
            }
          }
        }

        //popula j17_projetos
        foreach ($xml->{'DADOS-GERAIS'}->{'ATUACOES-PROFISSIONAIS'}->{'ATUACAO-PROFISSIONAL'} as $atuacao){
          if (isset($atuacao->{'ATIVIDADES-DE-PARTICIPACAO-EM-PROJETO'})){
            foreach ($atuacao->{'ATIVIDADES-DE-PARTICIPACAO-EM-PROJETO'} as $projeto) {
              for ($i=0; $i < count($projeto); $i++) {
                for ($j = 0; $j < count($projeto->{'PARTICIPACAO-EM-PROJETO'}[$i]->{'PROJETO-DE-PESQUISA'}); $j++){
                  if ($projeto->{'PARTICIPACAO-EM-PROJETO'}[$i]->{'PROJETO-DE-PESQUISA'}[$j]['NATUREZA'] == "PESQUISA") {
                    $titulo = $projeto->{'PARTICIPACAO-EM-PROJETO'}[$i]->{'PROJETO-DE-PESQUISA'}[$j]['NOME-DO-PROJETO'];
                    $descricao = $projeto->{'PARTICIPACAO-EM-PROJETO'}[$i]->{'PROJETO-DE-PESQUISA'}[$j]['DESCRICAO-DO-PROJETO'];
                    $titulo = str_replace("'","",$titulo);
                    $descricao = str_replace("'","",$descricao);
                    $inicio = $projeto->{'PARTICIPACAO-EM-PROJETO'}[$i]->{'PROJETO-DE-PESQUISA'}[$j]['ANO-INICIO'];
                    if($projeto->{'PARTICIPACAO-EM-PROJETO'}[$i]->{'PROJETO-DE-PESQUISA'}[$j]['ANO-FIM'] != ''){
                      $fim = $projeto->{'PARTICIPACAO-EM-PROJETO'}[$i]->{'PROJETO-DE-PESQUISA'}[$j]['ANO-FIM'];
                    } else {
                      $fim = 0;
                    }


                    if ($projeto->{'PARTICIPACAO-EM-PROJETO'}[$i]->{'PROJETO-DE-PESQUISA'}[$j]->{'EQUIPE-DO-PROJETO'}->{'INTEGRANTES-DO-PROJETO'}['FLAG-RESPONSAVEL'] == "SIM"){
                      $papel = "(Coordenador)";
                    } else {
                      $papel = "(Participante)";
                    }

                    $papel = str_replace("'","",$papel);

                    $fin = "";

                    if (isset($projeto->{'PARTICIPACAO-EM-PROJETO'}[$i]->{'PROJETO-DE-PESQUISA'}[$j]->{'FINANCIADORES-DO-PROJETO'})) {
                      foreach ($projeto->{'PARTICIPACAO-EM-PROJETO'}[$i]->{'PROJETO-DE-PESQUISA'}[$j]->{'FINANCIADORES-DO-PROJETO'} as $financiadores) {
                        for ($z=0; $z < count($financiadores); $z++) {
                          if ($financiadores->{'FINANCIADOR-DO-PROJETO'}[$z]['NOME-INSTITUICAO'] != "") {
                            $fin .= $financiadores->{'FINANCIADOR-DO-PROJETO'}[$z]['NOME-INSTITUICAO'].", ";
                          } else {
                            $fin .= "Não possui";
                          }
                        }
                      }
                    } else {
                      $fin .= "Não possui";
                    }

                    $integrantesProj = "";
                    if (isset($projeto->{'PARTICIPACAO-EM-PROJETO'}[$i]->{'PROJETO-DE-PESQUISA'}[$j]->{'EQUIPE-DO-PROJETO'})) {
                      foreach ($projeto->{'PARTICIPACAO-EM-PROJETO'}[$i]->{'PROJETO-DE-PESQUISA'}[$j]->{'EQUIPE-DO-PROJETO'} as $integrantes) {
                        foreach ($integrantes->{'INTEGRANTES-DO-PROJETO'} as $integrante) {
                          $integrantesProj .= $integrante['NOME-COMPLETO']."; ";
                        }
                      }
                    }
                    $fin = rtrim($fin, ", ");
                    $integrantesProj = rtrim($integrantesProj, "; ");
                    $sql = "INSERT INTO j17_projetos (id, idProfessor, titulo, descricao, inicio, fim, papel, financiadores, integrantes) VALUES (0, $idUsuario, '$titulo', '$descricao', '$inicio', '$fim', '$papel', '$fin', '$integrantesProj')";
                    Yii::$app->db->createCommand($sql)->execute();
                  }
                }
              }
            }
          }
        }

        //populacao publicacao conferencias
        $sql = "DELETE FROM j17_publicacoes WHERE idProfessor = $idUsuario AND tipo = 1";
        Yii::$app->db->createCommand($sql)->execute();

        foreach ($xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'TRABALHOS-EM-EVENTOS'} as $publicacao) {
          for ($i=0; $i < count($publicacao); $i++) {
            $titulo = $publicacao->{'TRABALHO-EM-EVENTOS'}[$i]->{'DADOS-BASICOS-DO-TRABALHO'}['TITULO-DO-TRABALHO'];
            $titulo = str_replace("'","",$titulo);
            $local = $publicacao->{'TRABALHO-EM-EVENTOS'}[$i]->{'DETALHAMENTO-DO-TRABALHO'}['NOME-DO-EVENTO'];
            $local = str_replace("'","",$local);
            $ano = $publicacao->{'TRABALHO-EM-EVENTOS'}[$i]->{'DADOS-BASICOS-DO-TRABALHO'}['ANO-DO-TRABALHO'];
            $tipo = 1;
            $natureza = ucwords(strtolower($publicacao->{'TRABALHO-EM-EVENTOS'}[$i]->{'DADOS-BASICOS-DO-TRABALHO'}['NATUREZA']));
            $natureza = str_replace("'","",$natureza);
            $natureza = substr($natureza, 0, 10);

            $autores = "";
            foreach ($publicacao->{'TRABALHO-EM-EVENTOS'}[$i]->{'AUTORES'} as $autor) {
              $autores .= ucwords(strtolower($autor['NOME-COMPLETO-DO-AUTOR']))."; ";
            }
            $autores = str_replace("'","",$autores);
            $sql = "INSERT INTO j17_publicacoes (idProfessor, titulo, ano, local, tipo, natureza, autores) VALUES ($idUsuario, '$titulo', '$ano', '$local', '$tipo', '$natureza', '$autores');";
            Yii::$app->db->createCommand($sql)->execute();
          }
        }

        //populacao publicacao periodicos
        $sql = "DELETE FROM j17_publicacoes WHERE idProfessor = $idUsuario AND tipo = 2";
        Yii::$app->db->createCommand($sql)->execute();
        foreach ($xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'ARTIGOS-PUBLICADOS'} as $publicacao) {
          for ($i=0; $i < count($publicacao); $i++) {
            $titulo = $publicacao->{'ARTIGO-PUBLICADO'}[$i]->{'DADOS-BASICOS-DO-ARTIGO'}['TITULO-DO-ARTIGO'];
            $titulo = str_replace("'","",$titulo);
            $local = $publicacao->{'ARTIGO-PUBLICADO'}[$i]->{'DETALHAMENTO-DO-ARTIGO'}['TITULO-DO-PERIODICO-OU-REVISTA'];
            $local = str_replace("'","",$local);
            $ano = $publicacao->{'ARTIGO-PUBLICADO'}[$i]->{'DADOS-BASICOS-DO-ARTIGO'}['ANO-DO-ARTIGO'];
            $tipo = 2;
            $autores = "";
            foreach ($publicacao->{'ARTIGO-PUBLICADO'}[$i]->{'AUTORES'} as $autor) {
              $autores .= ucwords(strtolower($autor['NOME-COMPLETO-DO-AUTOR']))."; ";
            }
            $autores = str_replace("'","",$autores);

            $sql = "INSERT INTO j17_publicacoes (idProfessor, titulo, ano, local, tipo, autores) VALUES ($idUsuario, '$titulo', '$ano', '$local', '$tipo', '$autores')";
            Yii::$app->db->createCommand($sql)->execute();
          }
        }

        //populacao publicaco livros
        $sql = "DELETE FROM j17_publicacoes WHERE idProfessor = $idUsuario AND tipo = 3";
        Yii::$app->db->createCommand($sql)->execute();

        if (isset($xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'LIVROS-E-CAPITULOS'}->{'LIVROS-PUBLICADOS-OU-ORGANIZADOS'})) {
          foreach ($xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'LIVROS-E-CAPITULOS'}->{'LIVROS-PUBLICADOS-OU-ORGANIZADOS'} as $publicacao) {
            for ($i=0; $i < count($publicacao); $i++) {
              $titulo = $publicacao->{'LIVRO-PUBLICADO-OU-ORGANIZADO'}[$i]->{'DADOS-BASICOS-DO-LIVRO'}['TITULO-DO-LIVRO'];
              $titulo = str_replace("'","",$titulo);
              $ano = $publicacao->{'LIVRO-PUBLICADO-OU-ORGANIZADO'}[$i]->{'DADOS-BASICOS-DO-LIVRO'}['ANO'];
              $tipo = 3;
              $sql = "INSERT INTO j17_publicacoes (idProfessor, titulo, ano, tipo) VALUES ($idUsuario, '$titulo', '$ano', '$tipo')";
              Yii::$app->db->createCommand($sql)->execute();
            }
          }
        }

        //populacao publicaco capitulos de livros
        $sql = "DELETE FROM j17_publicacoes WHERE idProfessor = $idUsuario AND tipo = 4";
        Yii::$app->db->createCommand($sql)->execute();

        if (isset($xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'LIVROS-E-CAPITULOS'}->{'CAPITULOS-DE-LIVROS-PUBLICADOS'})) {
          foreach ($xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'LIVROS-E-CAPITULOS'}->{'CAPITULOS-DE-LIVROS-PUBLICADOS'} as $publicacao) {
            for ($i=0; $i < count($publicacao); $i++) {
              $titulo = $publicacao->{'CAPITULO-DE-LIVRO-PUBLICADO'}[$i]->{'DADOS-BASICOS-DO-CAPITULO'}['TITULO-DO-CAPITULO-DO-LIVRO'];
              $titulo = str_replace("'","",$titulo);
              $ano = $publicacao->{'CAPITULO-DE-LIVRO-PUBLICADO'}[$i]->{'DADOS-BASICOS-DO-CAPITULO'}['ANO'];
              $local = $publicacao->{'CAPITULO-DE-LIVRO-PUBLICADO'}[$i]->{'DETALHAMENTO-DO-CAPITULO'}['TITULO-DO-LIVRO'];
              $local = str_replace("'","",$local);
              $tipo = 4;

              $sql = "INSERT INTO j17_publicacoes (idProfessor, titulo, ano, local, tipo) VALUES ($idUsuario, '$titulo', '$ano', '$local', '$tipo')";
              Yii::$app->db->createCommand($sql)->execute();
            }
          }
        }

        //Orientacoes em Andamento - Mestrado
        $sql = "DELETE FROM j17_orientacoes WHERE idProfessor = $idUsuario AND tipo = 2 AND status = 1";
    		Yii::$app->db->createCommand($sql)->execute();

    		foreach ($xml->{'DADOS-COMPLEMENTARES'}->{'ORIENTACOES-EM-ANDAMENTO'} as $orientacao) {
    			for ($i=0; $i < count($orientacao->{'ORIENTACAO-EM-ANDAMENTO-DE-MESTRADO'}); $i++) {
    				$titulo = $orientacao->{'ORIENTACAO-EM-ANDAMENTO-DE-MESTRADO'}[$i]->{'DADOS-BASICOS-DA-ORIENTACAO-EM-ANDAMENTO-DE-MESTRADO'}['TITULO-DO-TRABALHO'];
            $titulo = str_replace("'","",$titulo);
            $aluno = $orientacao->{'ORIENTACAO-EM-ANDAMENTO-DE-MESTRADO'}[$i]->{'DETALHAMENTO-DA-ORIENTACAO-EM-ANDAMENTO-DE-MESTRADO'}['NOME-DO-ORIENTANDO'];
            $aluno = str_replace("'","",$aluno);
            $ano = $orientacao->{'ORIENTACAO-EM-ANDAMENTO-DE-MESTRADO'}[$i]->{'DADOS-BASICOS-DA-ORIENTACAO-EM-ANDAMENTO-DE-MESTRADO'}['ANO'];
    				$tipo = 2;
    				$status = 1;

    				$sql = "INSERT INTO j17_orientacoes (idProfessor, titulo, aluno, ano, tipo, status) VALUES ($idUsuario, '$titulo', '$aluno', '$ano', $tipo, $status)";
    				Yii::$app->db->createCommand($sql)->execute();
    			}
    		}

        //Orientacoes em Andamento - Doutorado
        $sql = "DELETE FROM j17_orientacoes WHERE idProfessor = $idUsuario AND tipo = 3 AND status = 1";
    		Yii::$app->db->createCommand($sql)->execute();

    		foreach ($xml->{'DADOS-COMPLEMENTARES'}->{'ORIENTACOES-EM-ANDAMENTO'} as $orientacao) {
    			for ($i=0; $i < count($orientacao->{'ORIENTACAO-EM-ANDAMENTO-DE-DOUTORADO'}); $i++) {
    				$titulo = $orientacao->{'ORIENTACAO-EM-ANDAMENTO-DE-DOUTORADO'}[$i]->{'DADOS-BASICOS-DA-ORIENTACAO-EM-ANDAMENTO-DE-DOUTORADO'}['TITULO-DO-TRABALHO'];
            $titulo = str_replace("'","",$titulo);
            $aluno = $orientacao->{'ORIENTACAO-EM-ANDAMENTO-DE-DOUTORADO'}[$i]->{'DETALHAMENTO-DA-ORIENTACAO-EM-ANDAMENTO-DE-DOUTORADO'}['NOME-DO-ORIENTANDO'];
            $aluno = str_replace("'","",$aluno);
            $ano = $orientacao->{'ORIENTACAO-EM-ANDAMENTO-DE-DOUTORADO'}[$i]->{'DADOS-BASICOS-DA-ORIENTACAO-EM-ANDAMENTO-DE-DOUTORADO'}['ANO'];
    				$tipo = 3;
    				$status = 1;
    				$sql = "INSERT INTO j17_orientacoes (idProfessor, titulo, aluno, ano, tipo, status) VALUES ($idUsuario, '$titulo', '$aluno', '$ano', $tipo, $status)";
    				Yii::$app->db->createCommand($sql)->execute();
    			}
    		}

        //Orientacoes Concluídas - Graduação
        $sql = "DELETE FROM j17_orientacoes WHERE idProfessor = $idUsuario AND tipo = 1 AND status = 2";
    		Yii::$app->db->createCommand($sql)->execute();

    		foreach ($xml->{'OUTRA-PRODUCAO'}->{'ORIENTACOES-CONCLUIDAS'} as $orientacao) {
    			for ($i=0; $i < count($orientacao->{'OUTRAS-ORIENTACOES-CONCLUIDAS'}); $i++) {
    				$titulo = $orientacao->{'OUTRAS-ORIENTACOES-CONCLUIDAS'}[$i]->{'DADOS-BASICOS-DE-OUTRAS-ORIENTACOES-CONCLUIDAS'}['TITULO'];
            $titulo = str_replace("'","",$titulo);
            $aluno = $orientacao->{'OUTRAS-ORIENTACOES-CONCLUIDAS'}[$i]->{'DETALHAMENTO-DE-OUTRAS-ORIENTACOES-CONCLUIDAS'}['NOME-DO-ORIENTADO'];
            $aluno = str_replace("'","",$aluno);
            $ano = $orientacao->{'OUTRAS-ORIENTACOES-CONCLUIDAS'}[$i]->{'DADOS-BASICOS-DE-OUTRAS-ORIENTACOES-CONCLUIDAS'}['ANO'];
    				$natureza = ucwords(strtolower(str_replace("_", " ", $orientacao->{'OUTRAS-ORIENTACOES-CONCLUIDAS'}[$i]->{'DADOS-BASICOS-DE-OUTRAS-ORIENTACOES-CONCLUIDAS'}['NATUREZA'])));
            $natureza = str_replace("'","",$natureza);
            $tipo = 1;
    				$status = 2;
    				$sql = "INSERT INTO j17_orientacoes (idProfessor, titulo, aluno, ano, natureza, tipo, status) VALUES ($idUsuario, '$titulo', '$aluno', '$ano', '$natureza', $tipo, $status)";
    				Yii::$app->db->createCommand($sql)->execute();
    			}
    		}

        //Orientacoes Concluídas - Mestrado
        $sql = "DELETE FROM j17_orientacoes WHERE idProfessor = $idUsuario AND tipo = 2 AND status = 2";
    		Yii::$app->db->createCommand($sql)->execute();

    		foreach ($xml->{'OUTRA-PRODUCAO'}->{'ORIENTACOES-CONCLUIDAS'} as $orientacao) {
    			for ($i=0; $i < count($orientacao->{'ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'}); $i++) {
    				$titulo = $orientacao->{'ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'}[$i]->{'DADOS-BASICOS-DE-ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'}['TITULO'];
            $titulo = str_replace("'","",$titulo);
            $aluno = $orientacao->{'ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'}[$i]->{'DETALHAMENTO-DE-ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'}['NOME-DO-ORIENTADO'];
            $aluno = str_replace("'","",$aluno);
            $ano = $orientacao->{'ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'}[$i]->{'DADOS-BASICOS-DE-ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'}['ANO'];
    				$tipo = 2;
    				$status = 2;

    				$sql = "INSERT INTO j17_orientacoes (idProfessor, titulo, aluno, ano, tipo, status) VALUES ($idUsuario, '$titulo', '$aluno', '$ano', $tipo, $status)";
    				Yii::$app->db->createCommand($sql)->execute();
    			}
    		}

        //Orientacoes Concluídas - Doutorado
        $sql = "DELETE FROM j17_orientacoes WHERE idProfessor = $idUsuario AND tipo = 3 AND status = 2";
    		Yii::$app->db->createCommand($sql)->execute();

    		foreach ($xml->{'OUTRA-PRODUCAO'}->{'ORIENTACOES-CONCLUIDAS'} as $orientacao) {
    			for ($i=0; $i < count($orientacao->{'ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'}); $i++) {
    				$titulo = $orientacao->{'ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'}[$i]->{'DADOS-BASICOS-DE-ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'}['TITULO'];
            $titulo = str_replace("'","",$titulo);
            $aluno = $orientacao->{'ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'}[$i]->{'DETALHAMENTO-DE-ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'}['NOME-DO-ORIENTADO'];
            $aluno = str_replace("'","",$aluno);
            $ano = $orientacao->{'ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'}[$i]->{'DADOS-BASICOS-DE-ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'}['ANO'];
    				$tipo = 3;
    				$status = 2;

    				$sql = "INSERT INTO j17_orientacoes (idProfessor, titulo, aluno, ano, tipo, status) VALUES ($idUsuario, '$titulo', '$aluno', '$ano', $tipo, $status)";
    				Yii::$app->db->createCommand($sql)->execute();
    			}
    		}
        unlink($dir);
        $this->mensagens('success', 'Sucesso', 'Upload foi realizado com sucesso. ');
      } catch (\Exception $e) {
         $this->mensagens('danger', 'ERRO', 'Algum erro ocorreu.');
      }
    } else {
      $this->mensagens('danger', 'ERRO', 'Algum erro ocorreu.');
    }
  }
    return $this->render('lattes', ['model' => $model]);
  }

  /**
  * Uploads Lattes.
  * @return mixed
  */


  public function actionCvsdisciplinas()
  {
    $model = new UploadCvsdisciplinasForm();

    if (Yii::$app->request->isPost) {
      $model->csvDisciplinasFile = UploadedFile::getInstance($model, 'csvDisciplinasFile');
      if ($model->upload(Yii::$app->user->identity->id)) {
        // file is uploaded successfully
        $this->mensagens('success', 'Sucesso', 'Upload realizado com sucesso.');
        //return $this->redirect(['lattes');
      }
    }
    return $this->render('cvsdisciplinas', ['model' => $model]);
    //var_dump($model);
  }

  // Método que atualiza o banco com os formandos do período
  public function actionPit()
  {

    $token = "108FEF2DC23A626489596417D31C7729-".date("d-m-Y");
    $tokenMD5 = MD5($token);
    $link = 'http://200.129.163.42:8080/viper/listaFormados?cod_curso=IE08&ano_evasao=2016&periodo_evasao=1&sistema=PPGI&tkn='.$tokenMD5;
    var_dump($link);

    $webservice = @file_get_contents($link);

    // Verifica se o WS está disponivel
    //Caso negativo ele exibe o formulario em branco
    if($webservice == null)
    {
      echo "ERRO NO WEBSERVICE";

    }

    $dados = json_decode($webservice, true);

    var_dump($dados);

  }



  /**
  * Finds the User model based on its primary key value.
  * If the model is not found, a 404 HTTP exception will be thrown.
  * @param integer $id
  * @return User the loaded model
  * @throws NotFoundHttpException if the model cannot be found
  */
  protected function findModel($id)
  {
    if (($model = User::findOne($id)) !== null) {
      return $model;
    } else {
      throw new NotFoundHttpException('A página solicitada não existe.');
    }
  }

  /* Envio de mensagens para views
  Tipo: success, danger, warning*/
  protected function mensagens($tipo, $titulo, $mensagem){
    Yii::$app->session->setFlash($tipo, [
      'type' => $tipo,
      'icon' => 'home',
      'duration' => 5000,
      'message' => $mensagem,
      'title' => $titulo,
      'positonY' => 'top',
      'positonX' => 'center',
      'showProgressbar' => true,
    ]);
  }
}
