<?php
/**
 * SeekableIterator, построчное чтение файла, в том числе - большого
 * В зависимости от размера буфера чтения сохраняется привязка номера строки и позиции в файле.
 * Сохраняется первая позиция каждого читаемого буфера. Для 2гб файла и размера буфера 40к
 * - таблица занимает 50к элементов
 *
 * User: Ksnk
 * Date: 24.05.2018
 * Time: 16:34
 */

class iterator2gb implements SeekableIterator {

    private $position=0;

    /**
     * @var resource файловый хандл
     */
    private $handle=null;

    const MAXBUF=40000;

    // array of {position->filepos}
    private
    /** @var array массив соответствий позиций и смещений в файле */
        $cache=[],
    /** @var boolean дочитано до конца файла, кеш полон */
        $complete=false,
        $top=0, // максимальная прочитанная позиция
        $buf='',
        $start=0; /* начальная строка зачитаного буфера */

    /** @var array статистическая информация, для отладки
     * todo: выкинуть...
     */
    public $stat=['fread'=>0,'fclose'=>0,'fopen'=>0, 'fseek'=>0, 'invalidpos'=>0];

    /**
     * iterator2gb constructor. Можно сразу открыть нужный файл
     * @param bool $filename
     */
    public function __construct($filename=false){
        //parent::__construct();
        if(!!$filename){
            $this->open($filename);
        }
    }

    /**
     * Вроде не нужно, сейчас файлы и так закрываютсся, но пусть будет...
     */
    public function __destruct(){
        if(!empty($this->handle)) {
            fclose($this->handle);
        }
        //parent::__destruct();
    }

    /**
     * А можно потом открыть файл
     * @param $filename
     */
    public function open($filename){
        if(!empty($this->handle)) {
            fclose($this->handle);
        }

        if(is_readable($filename)) {
            $this->handle = fopen($filename, 'r+');
            $this->stat['fopen']++;
        } else
            $this->handle=false;
        $this->cache=[];
        $this->complete=false;
        $this->position=0;
    }

    /**
     * добиваем кэш до строки $pos
     */
    private function readtill($pos,$found=-1)
    {
        if($found<0) {
            $found=0;
            $this->cache[$found]=0; // присваиваем, на всякий случай
        }
        $fpos=$this->cache[$found];
        fseek($this->handle, $fpos); // один раз
        $this->stat['fseek']++;
        while (!feof($this->handle) && $this->top<=$pos) {
            // считаем строки
            $this->buf = fread($this->handle, self::MAXBUF);
            $this->stat['fread']++;
            // todo: проверить, не тут ли тормозим?
            if(preg_match_all('/\n/', $this->buf, $m, PREG_OFFSET_CAPTURE)) {
                $found = $this->top;
                $this->top += count($m[0]);
                $x = array_pop($m[0]);
                $this->cache[$this->top] = $fpos + $x[1] + 1;
            }

            $fpos += strlen($this->buf);
        }
        if(feof($this->handle)) {
            $this->complete = true;
        }
        return $found;
    }

    /**
     * Ищем пару для чтения буфера
     */
    private function scan($pos,&$found){
        // todo: искать бинарно?
        $found2=false;
        if(empty($this->cache)) return false;
        foreach($this->cache as $_pos=>$fpos){
            if($pos>=$_pos){
                $found=$_pos;
            } else {
                $found2=$_pos;
                break;
            }
        }
        return $found2;
    }

    /**
     * Обеспечиваем наличие строки с номером $pos в буфере
     * @param $pos
     */
    private function gotopos($pos){
        $found=-1;
        if(false===($fin=$this->scan($pos,$found))) { // нечитанное
            $found=$this->readtill($pos,$found);
        }
        if($this->start!=$found) {
            $fpos = $this->cache[$found];
            $this->start=$found;
            fseek($this->handle, $fpos);
            $this->stat['fseek']++;
            $this->buf = fread($this->handle, self::MAXBUF+4096); // до границы и еще чуток, некрасивенько...
            $this->stat['fread']++;
        }
    }

    private function isvalid($pos){
        if($pos<0 || !$this->handle ){
            return false;
        }
        if($this->top<$pos && !$this->complete) {
            $this->gotopos($pos);
        }

        return $pos<=$this->top;
    }

    /* Метод, требуемый для интерфейса SeekableIterator */

    public function seek($position) {
        if(!$this->isvalid($position)){
            $this->stat['invalidpos']++;
            throw new OutOfBoundsException("недействительная позиция ($position)");
        }
        $this->position = $position;
    }

    /*  Методы, требуемые для интерфейса Iterator */

    public function rewind() {
        $this->position = 0;
    }

    public function current() {
        $this->gotopos($this->position);
        $l=explode("\n",$this->buf);
        return $l[$this->position-$this->start];
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        ++$this->position;
    }

    public function valid() {
        return $this->isvalid($this->position);
    }
}