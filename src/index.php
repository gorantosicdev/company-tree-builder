<?php

class Travel {
  private string $id;
  private string $employeeName;
  private string $departure;
  private string $destination;
  private float  $price;
  private string $companyId;

  private ?string $createdAt;

  public function __construct(
    string $id,
    string $employeeName,
    string $departure,
    string $destination,
    float $price,
    string $companyId,
    ?string $createdAt
  ) {
    $this->id           = $id;
    $this->employeeName = $employeeName;
    $this->departure    = $departure;
    $this->destination  = $destination;
    $this->price        = $price;
    $this->companyId    = $companyId;
    $this->createdAt    = $createdAt;
  }

  public function getId(): string {
    return $this->id;
  }

  public function setId(string $id): void {
    $this->id = $id;
  }

  public function getEmployeeName(): string {
    return $this->employeeName;
  }

  public function setEmployeeName(string $employeeName): void {
    $this->employeeName = $employeeName;
  }

  public function getDeparture(): string {
    return $this->departure;
  }

  public function setDeparture(string $departure): void {
    $this->departure = $departure;
  }

  public function getDestination(): string {
    return $this->destination;
  }

  public function setDestination(string $destination): void {
    $this->destination = $destination;
  }

  public function getPrice(): float {
    return $this->price;
  }

  public function setPrice(float $price): void {
    $this->price = $price;
  }

  public function getCompanyId(): string {
    return $this->companyId;
  }

  public function setCompanyId(string $companyId): void {
    $this->companyId = $companyId;
  }


  public function getCreatedAt(): ?string {
    return $this->createdAt;
  }

  public function setCreatedAt(?string $createdAt): void {
    $this->createdAt = $createdAt;
  }

  public static function fromArray(array $data): Travel {
    return new self(
      $data['id'],
      $data['employeeName'],
      $data['departure'],
      $data['destination'],
      $data['price'],
      $data['companyId'],
      $data['createdAt'] ?? null
    );
  }
}

class Company {
  private string  $id;
  private string  $name;
  private ?string $parentId;

  private ?string $createdAt;

  /**
   * @var Company[]
   */
  private array $children = [];

  /**
   * @var Travel[]
   */
  private array $travels = [];

  public function __construct(
    string $id,
    string $name,
    ?string $parentId,
    ?string $createdAt
  ) {
    $this->id        = $id;
    $this->name      = $name;
    $this->parentId  = $parentId;
    $this->createdAt = $createdAt;
  }

  public function getId(): string {
    return $this->id;
  }

  public function setId(string $id): void {
    $this->id = $id;
  }

  public function getName(): string {
    return $this->name;
  }

  public function setName(string $name): void {
    $this->name = $name;
  }

  public function getParentId(): ?string {
    return $this->parentId;
  }

  public function setParentId(?string $parentId): void {
    $this->parentId = $parentId;
  }

  public function getCreatedAt(): ?string {
    return $this->createdAt;
  }

  public function setCreatedAt(?string $createdAt): void {
    $this->createdAt = $createdAt;
  }

  public function getChildren(): array {
    return $this->children;
  }

  public function setChildren(array $children): void {
    $this->children = $children;
  }

  public function addChild(Company $company): void {
    $this->children[] = $company;
  }

  public function getTravels(): array {
    return $this->travels;
  }

  public function setTravels(array $travels): void {
    $this->travels = $travels;
  }

  public function addTravel(Travel $travel): void {
    $this->travels[] = $travel;
  }

  public function getTotalCost(): float {
    $totalCost = array_reduce($this->getTravels(), function ($carry, $travel) {
      return $carry + $travel->getPrice();
    }, 0);

    foreach($this->getChildren() as $child) {
      $totalCost += $child->getTotalCost();
    }

    return $totalCost;
  }

  public function toJSON(): array {
    return [
      'id'        => $this->getId(),
      'createdAt' => $this->getCreatedAt(),
      'name'      => $this->getName(),
      'parentId'  => $this->getParentId(),
      'cost'      => $this->getTotalCost(),
      'children'  => array_map(function ($child) {
        return $child->toJSON();
      }, $this->getChildren()),
    ];
  }

  public static function fromArray(array $data): Company {
    return new self(
      $data['id'],
      $data['name'],
      $data['parentId'] ?? null,
      $data['createdAt'] ?? null
    );
  }
}

class TestScript {
  const COMPANY_LIST_API_URL = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies';
  const TRAVEL_LIST_API_URL  = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels';

  private function _fetchDataFromAPI(string $url): array {
    try {
      $jsonData = file_get_contents($url);

      if($jsonData === false) {
        die("Failed to fetch data from the API.");
      }

      $data = json_decode($jsonData, true);

      if($data === null || json_last_error() !== JSON_ERROR_NONE) {
        die('Failed to decode JSON data. JSON error: ' . json_last_error_msg());
      }

      return $data;

    } catch(\Throwable $throwable) {
      die("Failed to fetch data from the API. Error: " . $throwable->getMessage());
    }
  }

  private function _getTravels(): array {
    $travelsData = $this->_fetchDataFromAPI(self::TRAVEL_LIST_API_URL);

    $travelsData = array_filter($travelsData, function ($travelData) {
      return isset(
        $travelData['id'],
        $travelData['employeeName'],
        $travelData['departure'],
        $travelData['destination'],
        $travelData['companyId'],
        $travelData['price']);
    });

    $travels = [];

    foreach($travelsData as $travelData) {
      $travels[$travelData['companyId']][] = Travel::fromArray($travelData);
    }

    return $travels;
  }

  private function _getCompanies(): array {
    $companiesData = $this->_fetchDataFromAPI(self::COMPANY_LIST_API_URL);
    $travels = $this->_getTravels();

    $companiesData = array_filter($companiesData, function ($companyData) {
      return isset($companyData['id'], $companyData['name']);
    });

    $companies = [];

    foreach($companiesData as $companyData) {
      $company = Company::fromArray($companyData);

      if(isset($travels[$company->getId()])) {
        $company->setTravels($travels[$company->getId()]);
      }

      $companies[$company->getId()] = $company;
    }

    return $companies;
  }

  private function _buildTrees(): array {
    $companies = $this->_getCompanies();

    return array_reduce($companies, function ($result, $company) use ($companies) {
      if(isset($companies[$company->getParentId()])) {
        $companies[$company->getParentId()]->addChild($company);
      } else {
        $result[] = $company;
      }

      return $result;
    }, []);
  }

  public function execute(): void {
    $start = microtime(true);

    echo json_encode(array_map(function ($tree) {
      return $tree->toJSON();
    }, $this->_buildTrees()));

    echo 'Total time: ' . (microtime(true) - $start);
  }
}

(new TestScript())->execute();