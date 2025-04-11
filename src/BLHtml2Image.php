<?php
namespace BrenoLeite8\html2image;

use Adianti\Control\TPage;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;

class BLHtml2Image extends TPage
{
    public function __construct()
    {       
        parent::__construct();
        parent::include_js('vendor/brenoleite8/html2image/src/js/html2canvas.min.js');
    }
    public static function download(array $ids, $fileName = NULL, $zipName = NULL, $tempDir = 'tmp/')
    {
        try 
        {       
            $isLote = FALSE;    
            
            if(count($ids) > 1)
                $isLote = TRUE;

            if($isLote) {
                if(is_null($zipName))
                    $zipName = uniqid();

            } else {

                if(is_null($fileName))
                    $fileName[] = uniqid();
                
                
                $id   = reset($ids);
                $name = reset($fileName);
                $name = self::formata_texto($name);
                $path = $tempDir;
                /*
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
                          var filename = '$path/$name.png';
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
                */
                $script = "__adianti_block_ui();
                            var element = document.getElementById('$id');
                            
                            if (element) {
                                html2canvas(element).then(canvas => {
                                    var imgData = canvas.toDataURL('image/png');
                                    var params = {
                                        imgData: imgData,
                                        nomeArquivo: '$name'
                                    };
                            
                                    __adianti_ajax_exec({
                                        action: 'BLHtml2Image::saveImage',
                                        static: '1',
                                        parameters: params,
                                        complete: function() {
                                            console.log('Imagem salva com sucesso');
                                            var filename = '$path/$name.png';
                                            var downloadURL = '/download.php?file=' + filename + '&basename=';
                                            var link = document.createElement('a');
                                            link.href = downloadURL;
                                            link.download = filename;
                                            link.click();
                                            __adianti_unblock_ui();
                                        },
                                        error: function(request, status, error) {
                                            __adianti_failure_request(request, status, error);
                                            __adianti_unblock_ui();
                                        }
                                    });
                                }).catch(error => {
                                    console.error('Erro ao capturar elemento:', error);
                                    __adianti_unblock_ui();
                                });
                            } else {
                                console.error('Elemento não encontrado ou inválido.');
                                __adianti_unblock_ui();
                            }";

                TScript::create($script);
            }

        } catch (\Throwable $e) {
            new TMessage('error', $e->getMessage());
        }
        
    }

    public static function saveImage($param)
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
    public static function formata_texto ($text)
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
}
