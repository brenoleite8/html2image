<?php
namespace BrenoLeite8\html2image;

use Adianti\Control\TPage;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;

class BLHtml2Image extends TPage
{
    private $fileName;
    private $ids     = array(); 
    private $names   = array();
    private $tempDir = 'tmp/';
    private $isLote  = FALSE;

    public function __construct()
    {
        parent::__construct();
        TScript::create('vendor/brenoleite8/html2image/src/js/html2canvas.min.js');    
    }

    public function set_tempDir($tempDir)
    {
        $this->tempDir = $tempDir;
    }

    public function set_ids($ids)
    {
        $this->ids = $ids;
    }

    public function set_names($names)
    {
        $this->names = $names;
    }

    public function set_fileName($fileName)
    {
        $this->fileName = $fileName;
    }

    public function download()
    {
        try 
        {
            // VALIDAÇÕES
            if(empty($this->ids))
                throw new Exception('Os ids são obrigatórios!');
            if(empty($this->names))
                throw new Exception('Os nomes são obrigatórios!');
            if(empty($this->fileName))
                throw new Exception('O nome do arquivo é obrigatório!');
            
            if(count($this->ids) > 1)
                $this->isLote = TRUE;

            if($this->isLote) {

            } else {
                $id   = reset($this->ids);
                $name = reset($this->names);
                $path = $this->tempDir;
                $fileName = $this->fileName;
                $script = "
                __adianti_block_ui();
                var element = document.getElementById('$id');
                if (element) {
                  html2canvas(element).then(canvas => {
                        var imgData = canvas.toDataURL('image/png');
                        $.ajax({
                          type: 'POST',
                          url: 'engine.php?class=BLHtml2Image&method=saveImage&static=1',
                          data: {
                            imgData: imgData,
                            nomeArquivo: '$name'
                          }
                      }).done(function() {
                          console.log('Sucesso');
                          var filename = '$path/$fileName.png';
                          var downloadURL = '/download.php?file=' + filename + '&basename=';
                          var link = document.createElement('a');
                          link.href = downloadURL;
                          link.download = filename;
                          link.click();
                      }).fail(function(jqxhr, textStatus, exception) {
                         __adianti_failure_request(jqxhr, textStatus, exception);
                      });
                    })
                    .catch(error => {
                      console.error('Erro ao capturar elemento:', error);
                    });
                } else {
                  console.error('Elemento não encontrado ou inválido.');
                }
                __adianti_unblock_ui();
                ";

                TScript::create($script);
            }

        } catch (\Throwable $e) {
            new TMessage('error', $e->getMessage());
        }
        
    }

    private static function saveImage($param)
    {
        if(isset($param['isLote'])) {
            $images  = $param['images'];
            $names   = $param['names'];
            $zipFile = $param['fileName'].'.zip';
            $zipPath = $param['tempDir'] . $zipFile;

            if (!is_dir($param['tempDir'])) {
                mkdir($param['tempDir'], 0777, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                return ['error' => 'Erro ao criar ZIP'];
            }

            foreach ($images as $index => $imgData) {
                $imgData = str_replace('data:image/png;base64,', '', $imgData);
                $imgData = str_replace(' ', '+', $imgData);
                $data = base64_decode($imgData);

                $filePath = $param['tempDir'] . $names[$index];
                file_put_contents($filePath, $data);
                $zip->addFile($filePath, $names[$index]);
            }

            $zip->close();

            echo($zipPath);
        } else {
            $imgData = $param['imgData'];
            $imgData = str_replace('data:image/png;base64,', '', $imgData);
            $imgData = str_replace(' ', '+', $imgData);
            $data = base64_decode($imgData);
            $file = "tmp/{$param['nomeArquivo']}.png";
            file_put_contents($file, $data);
        }
    }

    // FORMATA O TEXTO PARA QUE NÃO DÊ ERRO NA HORA DE SALVAR O ARQUIVO
    protected static function formata_texto ($text)
    {
          // Converte acentos e caracteres especiais
          $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
      
          // Remove qualquer caractere que não seja letra, número, espaço, hífen ou underline
          $text = preg_replace('/[^A-Za-z0-9 _-]/', '', $text);
      
          // Substitui espaços por underlines (ou hífen, se preferir)
          $text = str_replace(' ', '_', $text);
      
          // Remove underlines ou hífens duplicados
          $text = preg_replace('/[_-]+/', '_', $text);
      
          // Remove underscores ou hífens do início/fim
          $text = trim($text, '_-');
      
          // Se estiver vazio, define um nome padrão
          if (empty($text)) {
              $text = 'arquivo';
          }
      
          return $text;
    }

    private function create()
    {
        if(!empty($this->fieldNames)) {
            if(!empty($this->objects))
                $this->objects = $this->formatDataAndColumns($this->objects, $this->fieldNames);
            if(!empty($this->rows))
                $this->rows    = $this->formatColumnNames($this->rows, $this->fieldNames);
            if(!empty($this->columns))
                $this->columns = $this->formatColumnNames($this->columns, $this->fieldNames);
        }
        $jsonData    = json_encode($this->objects);
        $jsonRows    = json_encode($this->rows);
        $jsonColumns = json_encode($this->columns);

        $script = "$(function(){
                            $('#".$this->id."').pivotUI(
                                ".$jsonData.",
                                {
                                    rows: ".$jsonRows.",
                                    cols: ".$jsonColumns."
                                }
                            , false, \"pt\");
                        });";
        TScript::create($script);
    }


    public function show()
    {
        $this->create();

        $script = new TElement('script');
        $script->type = 'text/javascript';
        $script->src  = 'vendor/brenoleite8/pivottable/src/js/pivot.min.js';

        $script_pt = new TElement('script');
        $script_pt->type = 'text/javascript';
        $script_pt->src  = 'vendor/brenoleite8/pivottable/src/js/pivot.pt.min.js';

        /*
        $script_plotly = new TElement('script');
        $script_plotly->type = 'text/javascript';
        $script_plotly->src  = 'vendor/brenoleite8/pivottable/src/js/plotly_renderers.min.js';

        $script_spec = new TElement('script');
        $script_spec->type = 'text/javascript';
        $script_spec->src  = 'vendor/brenoleite8/pivottable/src/js/pivot_spec.min.js';
        
        $script_gchart = new TElement('script');
        $script_gchart->type = 'text/javascript';
        $script_gchart->src  = 'vendor/brenoleite8/pivottable/src/js/gchart_renderers.min.js';

        $script_export = new TElement('script');
        $script_export->type = 'text/javascript';
        $script_export->src  = 'vendor/brenoleite8/pivottable/src/js/export_renderers.min.js';

        $script_d3 = new TElement('script');
        $script_d3->type = 'text/javascript';
        $script_d3->src  = 'vendor/brenoleite8/pivottable/src/js/d3_renderers.min.js';

        $script_c3 = new TElement('script');
        $script_c3->type = 'text/javascript';
        $script_c3->src  = 'vendor/brenoleite8/pivottable/src/js/c3_renderers.min.js';
       */
        
        $content = new TElement('div');
        $content->id = $this->id;
                
        //return  $script.$script_pt.$script_plotly.$script_spec.$script_gchart.$script_export.$script_d3.$script_c3.$content;
        return  $script.$script_pt.$content;
    }

}
