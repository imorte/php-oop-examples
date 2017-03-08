<?php
declare(strict_types = 1);

require_once 'vendor/autoload.php';
error_reporting(E_ALL);
class ShopProduct {
    private $title;
    private $producerMainName;
    private $producerFirstName;
    protected $price;
    private $discount = 0;
    private $id = 0;

    public function __construct($title, $firstName, $mainName, $price) {
        $this->title = $title;
        $this->producerFirstName = $firstName;
        $this->producerMainName = $mainName;
        $this->price = $price;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public static function getInstance($id, PDO $pdo) {
        $stmt = $pdo->prepare("select * from products where id=?");
        $stmt->execute([$id]);

        $row = $stmt->fetch();

        if(empty($row)) return null;

        if($row['type'] == 'book') {
            $product = new BookProduct($row['title'], $row['firstname'], $row['mainname'],
                $row['price'], $row['numpages']);
        } elseif ($row['type'] == 'cd') {
            $product = new CDProduct($row['title'], $row['firstname'], $row['mainname'],
                $row['price'], $row['playlength']);
        } else {
            $product = new ShopProduct($row['title'], $row['firstname'], $row['mainname'],
                $row['price']);
        }

        $product->setId($row['id']);
        $product->setDiscount($row['discount']);
        return $product;
    }

    public function getProducerFirstName() {
        return $this->producerFirstName;
    }

    public function getProducerMainName() {
        return $this->producerMainName;
    }

    public function setDiscount($num) {
        $this->discount = $num;
    }

    public function getDiscount() {
        return $this->discount;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getPrice() {
        return $this->price - $this->discount;
    }

    public function getProducer() {
        return "{$this->producerFirstName} {$this->producerMainName}";
    }

    public function getSummaryLine() {
        $base = "{$this->title} ({$this->producerMainName}, {$this->producerFirstName})";
        return $base;
    }
}

class CDProduct extends ShopProduct {
    private $playLength;

    public function __construct($title, $firstName, $mainName, $price, $playLength)
    {
        parent::__construct($title, $firstName, $mainName, $price);
        $this->playLength = $playLength;
    }

    public function getPlayLength() {
        return $this->playLength;
    }

    public function getSummaryLine()
    {
        $base = parent::getSummaryLine();
        $base .= ": Song time: {$this->playLength}";
        return $base;
    }
}

class BookProduct extends ShopProduct {
    private $numPages;

    public function __construct($title, $firstName, $mainName, $price, $numPages)
    {
        parent::__construct($title, $firstName, $mainName, $price);
        $this->numPages = $numPages;
    }

    public function getNumberOfPages() {
        return $this->numPages;
    }

    public function getSummaryLine()
    {
        $base = parent::getSummaryLine();
        $base .= ": {$this->numPages} pages";
        return $base;
    }

    public function getPrice()
    {
        return $this->price;
    }
}

abstract class ShopProductWriter {
    protected $products = [];

    public function addProduct(ShopProduct $shopProduct) {
        $this->products[] = $shopProduct;
    }

    abstract public function write();
}

$book = new BookProduct('Book', 'Kirill', 'P', 10, 300);
$music = new CDProduct('a7x', 'Kirill', 'P', 10, 400);



$dsn = "sqlite://Users/kirill/webroot/oopbook/db.sqlite3";
$pdo = new PDO($dsn, null, null);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$obj = ShopProduct::getInstance(1, $pdo);


class XmlProductWriter extends ShopProductWriter {
    
    public function write()
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
            $writer->startElement('products');
            foreach ($this->products as $product) {
                $writer->startElement('product');
                $writer->writeAttribute('title', $product->getTitle());
                    $writer->startElement('summary');
                    $writer->text($product->getSummaryLine());
                    $writer->endElement();
                $writer->endElement();
            }
            $writer->endElement();
        $writer->endDocument();
        echo $writer->flush();
    }
}

class TextProductWriter extends ShopProductWriter {
    public function write()
    {
        $str = "\nТовары:\n";
        foreach ($this->products as $product) {
            $str .= $product->getSummaryLine() . "\n";
        }

        echo $str;
    }
}

header('Content-Type: application/xml');

$x = new XmlProductWriter();
$x->addProduct($book);
$x->addProduct($music);

$x->write();