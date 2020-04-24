<?php
namespace AppBundle\Service;

class AvailabilityXml
{
    private $ftpServer = '217.114.93.66';
    private $ftpUser = 'atcore_prod';
    private $ftpPassword = 'Atcore@1';
    private $files = [
        'PKG_BT_DA.XML.gz',
        'PKG_SR_SV.XML.gz',
        'PKG_LM_FI.XML.gz',
        'PKG_SO_NO.XML.gz',
        'PKG_HF_IS.XML.gz',
        'PKG_ST_DA.XML.gz'
    ];
    
    private $directory;
    private $threshold = 60*60*24*2; // 48 hours
    
    public function __construct($directory) {
        date_default_timezone_set('Europe/Copenhagen');
        $this->directory = $directory;
    }
    
    public function checkFiles() {
        $connId = ftp_connect($this->ftpServer);
        $loginResult = ftp_login($connId, $this->ftpUser, $this->ftpPassword);

        $files = [];
        foreach ($this->files as $file) {
            $files[] = [
                'name' => $file,
                'last_modified' => ftp_mdtm($connId, $file),
                'size' => ftp_size($connId, $file)
            ];
        }

        ftp_close($connId);
        
        return $files;
    }
    
    public function fileExists($file) {
        if (file_exists($this->directory . '/' . $file)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function downloadLatestFiles() {
        $messages = [];
        
        $connId = ftp_connect($this->ftpServer);
        $loginResult = ftp_login($connId, $this->ftpUser, $this->ftpPassword);
        
        foreach ($this->files as $file) {
            $lastModified = ftp_mdtm($connId, $file);
            $fileBackupName = substr($file, 0, -7) . '_' . date('YmdHi', $lastModified) . '.XML.gz';
            if (!$this->fileExists($fileBackupName)) {
                
                $size = ftp_size($connId, $file);
                if ($size < 1000) { // check if file is less than 1Kb
                    $message = \Swift_Message::newInstance()
                        ->setSubject('AVLABL XML too small')
                        ->setFrom(['utils@primerait.com' => 'Utils'])
                        ->setTo([
                            'support@primerait.com' => 'Primera IT',
                        ])
                        ->setCC([
                            'anders@primerait.com' => 'Anders Hal Werner',
                            'nol@primerait.com' => 'Nicolai Olsen',
                            'mmg@primerait.com' => 'Marcos Moro Greasley',
                        ])
                        ->setBody(
                            $this->renderView(
                                'emails/avlabl-alert.html.twig',
                                [
                                    'file' => $file,
                                    'size' => $size
                                ]
                            ),
                            'text/html'
                        );
                    $this->get('mailer')->send($message);
                }
                
                if (ftp_get($connId, $this->directory . '/' . $fileBackupName, $file, FTP_BINARY)) {
                    $messages[] = 'Successfully stored backup of ' . $file . ' to ' . $this->directory . '/' . $fileBackupName . '.';
                } else {
                    $messages[] = 'Error storing backup of ' . $file . ' to ' . $this->directory . '/' . $fileBackupName . '.';
                }
                
            } else {
                $messages[] = 'Skipping ' . $file . ' because we already have a backup.';
            }
        }
        
        return $messages;
    }
    
    public function removeOldFiles() {
        $messages = [];
        
        if (file_exists($this->directory)) {
            foreach (new \DirectoryIterator($this->directory) as $fileInfo) {
                if ($fileInfo->isDot()) {
                    continue;
                }
                if ((time() - $fileInfo->getCTime()) >= $this->threshold) {
                    $messages[] = 'Deleted ' . $fileInfo->getFilename() . ' from ' . $this->directory . '.';
                    unlink($fileInfo->getRealPath());
                } else {
                    $messages[] = 'Keeping ' . $fileInfo->getFilename() . ' because it is not old enough.';
                }
            }
        }
        
        return $messages;
    }

    public function getBackupFiles($fileName) {
        $files = [];
        
        if (file_exists($this->directory)) {
            foreach (new \DirectoryIterator($this->directory) as $fileInfo) {
                if (substr($fileInfo->getFilename(), 0, -20) == substr($fileName, 0, -7)) {
                    $files[] = [
                        'name' => $fileInfo->getFilename(),
                        'size' => $fileInfo->getSize(),
                    ];
                }
            }
        }
        
        usort($files, [$this, 'cmp']);
        return $files;
    }
    
    private function cmp($a, $b) {
        return strcmp($a['name'], $b['name']);
    }
}