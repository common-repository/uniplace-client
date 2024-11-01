	<?
  /**
  * Срипт обновления клиента Uniplace
  *
  * class UniplacerUpdate - класс для обновления клиента
  * 
  * @version 1.0.21 от 25.07.2013
  */

  #_ZIP_NAME_Update.php_# // Строчку убирать НЕЛЬЗЯ - это идентификатор файла для скрипта обновления

  require_once($_SERVER['DOCUMENT_ROOT'].'/uniplacer_config.php');

  define("UNIPLACE_UWSUPDATEHOST", "api.uniplace.ru"); // Сервер обновления
  define('UNIPLACE_UWSUPDATEURL', "GetUpdate.axd"); // URL сервера с обновлением
  define('UNIPLACE_USOCKETPORT', 80); // Порт обращения к серверу обновлений
  define('UNIPLACE_USOCKETTIME', 30); // Максимальное время ожидания ответа от сервера

  /**
  * Класс UniplacerUpdate
  * Обновление клиента Uniplacer
  */
  class UniplacerUpdate{

    /**
    * @var UniplacerOpID Идентификатор операции
    */
    var $UniplacerOpID = "";

    /**
    * @var UniplacerUpdatePath Путь к папке с обновлениями
    */
    var $UniplacerUpdatePath = "";

    /**
    * @var UniplacerBackupPath Путь к папке с бэкапом
    */
    var $UniplacerBackupPath = "";

    /**
    * @var UniplacerUpdateFiles Массив с файлами доступными для обновления
    */
    var $UniplacerUpdateFiles = Array();

    /**
    * @var UniplacerUpdateArchive Содержимое архива с обновлениями
    */
    var $UniplacerUpdateArchive = "";

    /**
    * Конструктор
    */
    function UniplacerUpdate($UniplacerOpID){
      $this->UniplacerOpID = $UniplacerOpID;        
      $this->UniplacerUpdatePath = $_SERVER["DOCUMENT_ROOT"]."/"._UNIPLACE_USER_."/update";      
      $this->UniplacerBackupPath = $_SERVER["DOCUMENT_ROOT"]."/"._UNIPLACE_USER_."/backup";      
    }        

    /**
    * Замена файлов с возможным сохранением в папку бэкапа
    * @param string $filename Имя файла
    * @param boolean $needBacup Флаг необходимости сохранения резервной копии заменяемого при обновлении файла
    * 
    * @return boolean Успешность завершения операции
    */
    function replace_file_with_backup($filename, $needBackup = false){
      $from = $_SERVER["DOCUMENT_ROOT"]."/"._UNIPLACE_USER_."/update/".$filename;
      $to = $_SERVER["DOCUMENT_ROOT"]."/"._UNIPLACE_USER_."/".$filename;
      $backup = $this->UniplacerBackupPath."/".$filename;

      if(!file_exists($from)) return false;
      if(!file_exists($to)) return false;

      if($needBackup){
        if(!file_exists($this->UniplacerBackupPath)){
          if(@mkdir($this->UniplacerBackupPath)){ @chmod($this->UniplacerBackupPath, 0777); }
          else return false;
        }

        if(file_exists($backup)){
          @chmod($backup, 0777);
          @unlink($backup);
        }

        if(!@copy($to, $backup)) return false;          
      }

      if(!@unlink($to)) return false;
      if(!@copy($from, $to))  return false;
      if(!@unlink($from)) return false;

      return true;
    }

    /**
    * Рекурсивное удаление каталога(непустое)
    * @param string $dir Удаляемая директория
    */
    function delete_catalog($dir){
      if(file_exists($dir)){
        @chmod($dir,0777);
        if(@is_dir($dir)){
          $handle = @opendir($dir);
          while($filename = @readdir($handle)){
            if ($filename != "." && $filename != ".."){
              $this->delete_catalog($dir."/".$filename);
            }
          }
          @closedir($handle);
          @rmdir($dir);
        }
        else @unlink($dir);
      }
    }

    /**
    * Получения архива с обновлениями с удалённого сервера
    */
    function GetUpdateArchive(){
      $fHandler = fsockopen(UNIPLACE_UWSUPDATEHOST, UNIPLACE_USOCKETPORT, $errno, $errstr, UNIPLACE_USOCKETTIME);
      if (!$fHandler) return false;

      $host = UNIPLACE_UWSUPDATEHOST;
      $path = "/".UNIPLACE_UWSUPDATEURL."?qlid=".$this->get_code()."&opid=".$this->UniplacerOpID;

      @fputs($fHandler, "GET {$path} HTTP/1.0\r\nHost: {$host}\r\n");
      @fputs($fHandler, "User-Agent: Uniplace PHP Client\r\n\r\n");

      $content = "";

      while(fgets($fHandler,1024)!="\r\n" && !feof($fHandler));
      while (!feof($fHandler)){
        $content .= @fgets($fHandler,1024);          
      }

      @fclose($fHandler);

      $this->UniplacerUpdateArchive = $content;
      return true;
    }

    function get_code(){
      return substr(md5(_UNIPLACE_USER_), 0, 8);
    }

    /**
    *  Вызов хэндлера для сообщения об успешном обновлении скрипта
    */
    function SendUpdateResult(){
      $fHandle = @fopen("qlinkop.id", "w");
      @fwrite($fHandle, $this->UniplacerOpID);
      @fclose($fHandle);
      @chmod($_SERVER["DOCUMENT_ROOT"]."/"._UNIPLACE_USER_."/qlinkop.id", 0777);
    }   

    /**
    * Сохранение полученного архива в папке с обновлениями
    */
    function SaveArchive(){
      if(file_exists($this->UniplacerUpdatePath)) $this->delete_catalog($this->UniplacerUpdatePath);

      if(@mkdir($this->UniplacerUpdatePath)){
        @chmod($this->UniplacerUpdatePath, 0777);

        $uHandler = @fopen($this->UniplacerUpdatePath."/update.zip","w");
        if(!$uHandler) return false;

        @fwrite($uHandler, $this->UniplacerUpdateArchive);
        @fclose($uHandler);
        @chmod($this->UniplacerUpdatePath."/update.zip", 0777);  

        return true;
      } else return false; 
    }


    /**
    *  Распаковка архива с обновлениями
    */
    function UnpackArchive(){
      if(!function_exists('zip_open')) return false;

      $zip = zip_open($_SERVER["DOCUMENT_ROOT"]."/"._UNIPLACE_USER_."/update/update.zip"); 
      if ($zip) { 
        while ($zip_entry = zip_read($zip)) { 
          if(zip_entry_open($zip, $zip_entry, "r")) { 
            $buf = "";
            $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)); 
            zip_entry_close($zip_entry); 
            if (preg_match("#_ZIP_NAME_(.*?)_#si",$buf,$ok)){
              $this->UniplacerUpdateFiles[$ok[1]] = $buf;
            }              
          }             
        } 
        zip_close($zip);   
      }

      return true;  
    }   


    /**
    *  Обновление клиента
    */
    function Update(){
      // Получим архив с удалённого сервера
      if(!$this->GetUpdateArchive()) return false;

      // Сохраним
      if(!$this->SaveArchive()) return false;

      //Распакуем
      if(!$this->UnpackArchive()) return false;


      // Сохраним файлы из архива в папку с обновлением
      if(count($this->UniplacerUpdateFiles)){
        foreach($this->UniplacerUpdateFiles as $filename => $filedata){

          $f = @fopen($this->UniplacerUpdatePath."/".$filename, "w");
          if(!$f) return false;
          @fwrite($f, $filedata);
          @fclose($f);
          @chmod($this->UniplacerUpdatePath."/".$filename,0777);

          if($filename !== "Update.php"){
            // Начнём заменять файлы, кроме файла Update.php - он заменится хвостом в uniplacer.php
            // Заменяем с сохранением предыдущей версии файла в папке backup
            if(!$this->replace_file_with_backup($filename, true)) return false;
          }
        }        
      }else return false;

      @unlink($_SERVER["DOCUMENT_ROOT"]."/"._UNIPLACE_USER_."/update/update.zip");

      return true;
    }
  }

  if($_GET["opid"]){
    $UniplacerUpdate = new UniplacerUpdate($_GET["opid"]);
    if($UniplacerUpdate->Update()){
      $UniplacerUpdate->SendUpdateResult();
      echo "{result: success}";
    }else echo "{result: fail}";
  }
?>