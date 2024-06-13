<?php

namespace Tests\Serialize;

use ByJG\Serializer\Formatter\JsonFormatter;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use ByJG\Serializer\Formatter\XmlFormatter;
use ByJG\Serializer\Serialize;
use ByJG\Serializer\SerializerObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Sample\ModelForceProperty;
use Tests\Sample\ModelGetter;
use Tests\Sample\ModelList;
use Tests\Sample\ModelList2;
use Tests\Sample\ModelList3;
use Tests\Sample\ModelPublic;

class SerializerObjectTest extends TestCase
{
    public function testCreateObjectFromModel_ObjectGetter_1elem()
    {
        $model = new ModelGetter(10, 'Joao');

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            ['Id' => 10, 'Name' => 'Joao'],
            $result
        );

        $this->assertEquals(
            '{"Id":10,"Name":"Joao"}',
            (new JsonFormatter())->process($result)
        );

        $this->assertEquals(
            "10\nJoao\n",
            (new PlainTextFormatter())->process($result)
        );

        $this->assertEquals(
            "<div>10</div><div>Joao</div>",
            (new PlainTextFormatter())->withBreakLine("</div>")->withStartOfLine("<div>")->process($result)
        );

        $this->assertEquals(
            "<?xml version=\"1.0\"?>\n<root><Id>10</Id><Name>Joao</Name></root>\n",
            (new XmlFormatter())->process($result)
        );

    }

    public function testCreateObjectFromModel_ObjectGetter_2elem()
    {
        $model = array(
            new ModelGetter(10, 'Joao'),
            new ModelGetter(20, 'JG')
        );

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            [
                ['Id' => 10, 'Name' => 'Joao'],
                ['Id' => 20, 'Name' => 'JG']
            ]
            , $result
        );

        $this->assertEquals(
            '[{"Id":10,"Name":"Joao"},{"Id":20,"Name":"JG"}]',
            (new JsonFormatter())->process($result)
        );

        $this->assertEquals(
            "10\nJoao\n\n20\nJG\n\n",
            (new PlainTextFormatter())->process($result)
        );

        $this->assertEquals(
            "<div><div>10</div><div>Joao</div></div><div><div>20</div><div>JG</div></div>",
            (new PlainTextFormatter())->withBreakLine("</div>")->withStartOfLine("<div>")->process($result)
        );

        $this->assertEquals(
            "<?xml version=\"1.0\"?>\n<root><item0><Id>10</Id><Name>Joao</Name></item0><item1><Id>20</Id><Name>JG</Name></item1></root>\n",
            (new XmlFormatter())->withListElementSuffix()->process($result)
        );

    }

    public function testCreateObjectFromModel_ObjectPublic_1elem()
    {
        $model = new ModelPublic(10, 'Joao');

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            ['Id' => 10, 'Name' => 'Joao'],
            $result
        );
    }

    public function testCreateObjectFromModel_ObjectPublic_2elem()
    {
        $model = array(
            new ModelPublic(10, 'Joao'),
            new ModelPublic(20, 'JG')
        );

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            [
                ['Id' => 10, 'Name' => 'Joao'],
                ['Id' => 20, 'Name' => 'JG']
            ]
            , $result
        );
    }

    public function testCreateObjectFromModel_StdClass_1()
    {
        $model = new stdClass();
        $model->Id = 10;
        $model->Name = 'Joao';

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            ['Id' => 10, 'Name' => 'Joao'],
            $result
        );
    }

    public function testCreateObjectFromModel_StdClass_Model()
    {
        $model = new stdClass();
        $model->Id = 10;
        $model->Name = 'Joao';
        $model->Object = new ModelGetter(20, 'JG');

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            ['Id' => 10, 'Name' => 'Joao', 'Object' => ['Id' => 20, 'Name'=>'JG']],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_1()
    {
        $model = [
            'Id' => 10,
            'Name' => 'Joao'
        ];

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            ['Id' => 10, 'Name' => 'Joao'],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_2()
    {
        $model = [
            'Id' => 10,
            'Name' => 'Joao',
            'Data' =>
            [
                'Code' => '2',
                'Sector' => '3'
            ]
        ];

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            [
                'Id' => 10,
                'Name' => 'Joao',
                'Data' =>
                    [
                        'Code' => '2',
                        'Sector' => '3'
                    ]
            ],
            $result
        );
    }

    public function testCreateObjectFromModel_StdClass_Array()
    {
        $model = new stdClass();
        $model->Obj = [
            'Id' => 10,
            'Name' => 'Joao'
        ];

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            [
                "Obj" => ['Id' => 10, 'Name' => 'Joao']
            ],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_Scalar()
    {
        $model = new stdClass();
        $model->Obj = [
            10,
            'Joao'
        ];

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            ["Obj" => [10, 'Joao']],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_Mixed()
    {
        $model = new stdClass();
        $model->Obj = [
            10,
            'Joao',
            new ModelGetter(20, 'JG')
        ];

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            ["Obj" => [10, 'Joao', ['Id'=>20, 'Name'=>'JG']]],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_Array()
    {
        $model = new stdClass();
        $model->Obj = [
            'Item1' =>
            [
                10,
                'Joao'
            ],
            'Item2' =>
            [
                20,
                'JG'
            ]
        ];

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            [
                'Obj' => [
                    'Item1' =>
                        [
                            10,
                            'Joao'
                        ],
                    'Item2' =>
                        [
                            20,
                            'JG'
                        ]
                ]
            ],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_Array_2()
    {
        $model = new stdClass();
        $model->Obj = [
            [
                10,
                'Joao'
            ]
        ];

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            [
                'Obj' => [
                    [
                        10,
                        'Joao'
                    ]
                ]
            ], $result
        );
    }

    public function testCreateObjectFromModel_Array_Array_3()
    {
        $model = [
            [
                'Id' => 10,
                'Name' => 'Joao'
            ],
            [
                'Id' => 11,
                'Name' => 'Gilberto'
            ],
        ];

        $object = Serialize::From($model);

        $result = $object->toArray();

        $this->assertEquals(
            [
                [
                    'Id' => 10,
                    'Name' => 'Joao'
                ],
                [
                    'Id' => 11,
                    'Name' => 'Gilberto'
                ],
            ],
            $result
        );
    }

    public function testCreateObjectFromModel_Array_Array_5()
    {
        $model = new stdClass;

        $model->Title = 'testing';
        $model->List = [
            [
                'Id' => 10,
                'Name' => 'Joao'
            ],
            [
                'Id' => 11,
                'Name' => 'Gilberto'
            ],
        ];
        $model->Group = "test";

        $object = Serialize::From($model);

        $result = $object->toArray();

        $this->assertEquals(
            [
                'Title' => 'testing',
                'List' => [
                    [
                        'Id' => 10,
                        'Name' => 'Joao'
                    ],
                    [
                        'Id' => 11,
                        'Name' => 'Gilberto'
                    ]
                ],
                "Group" => "test"
            ],
            $result
        );
    }

    /**
     * @todo: Interpret Annotations
     */
    public function testCreateObjectFromModel_Collection_DontCreateNode()
    {
        $modellist = new ModelList();
        $modellist->addItem(new ModelGetter(10, 'Joao'));
        $modellist->addItem(new ModelGetter(20, 'JG'));

        $object = Serialize::From($modellist);
        $result = $object->toArray();

        $this->assertEquals(
            [
                "collection" =>
                [
                    ["Id"=>10, "Name"=>"Joao"],
                    ["Id"=>20, "Name"=>"JG"]
                ]
            ],
            $result
        );
    }

    /**
     * @todo: Interpret Annotations
     */
    public function testCreateObjectFromModel_Collection_CreateNode()
    {
        $modellist = new ModelList2();
        $modellist->addItem(new ModelGetter(10, 'Joao'));
        $modellist->addItem(new ModelGetter(20, 'JG'));

        $object = Serialize::From($modellist);
        $result = $object->toArray();

        $this->assertEquals(
            [
                "collection" =>
                    [
                        ["Id"=>10, "Name"=>"Joao"],
                        ["Id"=>20, "Name"=>"JG"]
                    ]
            ],
            $result
        );
    }

    /**
     * @todo: Interpret Annotations
     */
    public function testCreateObjectFromModel_Collection_SkipParentAndRenameChild()
    {
        $modellist = new ModelList3();
        $modellist->addItem(new ModelGetter(10, 'Joao'));
        $modellist->addItem(new ModelGetter(20, 'JG'));

        $object = Serialize::From($modellist);
        $result = $object->toArray();

        $this->assertEquals(
            [
                "collection" =>
                    [
                        ["Id"=>10, "Name"=>"Joao"],
                        ["Id"=>20, "Name"=>"JG"]
                    ]
            ],
            $result
        );

        $object = Serialize::From($modellist->getCollection());
        $result = $object->toArray();

        $this->assertEquals(
            [
                ["Id"=>10, "Name"=>"Joao"],
                ["Id"=>20, "Name"=>"JG"]
            ],
            $result
        );
    }

    public function testCreateObjectFromModel_OnlyScalarAtFirstLevel()
    {
        $model = [
            10,
            'Joao'
        ];

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            [
                10,
                'Joao'
            ],
            $result
        );
    }

    public function testEmptyValues()
    {
        $model = new stdClass();
        $model->varFalse = false;
        $model->varTrue = true;
        $model->varZero = 0;
        $model->varZeroStr = '0';
        $model->varNull = null;
        $model->varEmptyString = '';

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            [
                'varFalse' => false,
                'varTrue' => true,
                'varZero' => 0,
                'varZeroStr' => '0',
                'varNull' => null,
                'varEmptyString' => ''
            ],
            $result
        );

        $object->withOnlyString()->toArray();

        $this->assertEquals(
            [
                'varFalse' => '',
                'varTrue' => '1',
                'varZero' => '0',
                'varZeroStr' => '0',
                'varNull' => '',
                'varEmptyString' => ''
            ],
            $result
        );

        $result = $object->withOnlyString(false)->withDoNotParseNullValues()->toArray();

        $this->assertEquals(
            [
                'varFalse' => false,
                'varTrue' => true,
                'varZero' => 0,
                'varZeroStr' => '0',
                'varEmptyString' => ''
            ],
            $result
        );

    }

    public function testEmptyValues_2()
    {
        $model = new ModelPublic(null, 'Joao');

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            ['Id'=>null, 'Name'=>'Joao'],
            $result
        );

        $result = $object->withDoNotParseNullValues()->toArray();

        $this->assertEquals(
            ['Name'=>'Joao'],
            $result
        );

        $model = new ModelPublic(null, null);
        $object = Serialize::From($model);

        $result = $object->withDoNotParseNullValues()->toArray();

        $this->assertEquals(
            [],
            $result
        );
    }

    public function testEmptyValues_3()
    {
        $model = new ModelGetter(null, 'Joao');

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            ['Id'=>null, 'Name'=>'Joao'],
            $result
        );

        $result = $object->withDoNotParseNullValues()->toArray();

        $this->assertEquals(
            ['Name'=>'Joao'],
            $result
        );
    }

    public function testEmptyValues_4()
    {
        $model = new ModelList();

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            [
                'collection' => null
            ],
            $result
        );

        $result = $object->withDoNotParseNullValues()->toArray();

        $this->assertEquals(
            [],
            $result
        );
    }

    public function testEmptyValues_5()
    {
        $model = new ModelList();
        $model->addItem(new ModelGetter(null, 'Joao'));
        $model->addItem(new ModelGetter(null, null));

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            [
                'collection' => [
                    [
                        'Id' => null,
                        'Name' => 'Joao'
                    ],
                    [
                        'Id' => null,
                        'Name' => null
                    ]
                ]
            ],
            $result
        );

        $result = $object->withDoNotParseNullValues()->toArray();

        $this->assertEquals(
            [
                'collection' => [
                    [
                        'Name' => 'Joao'
                    ],
                    [
                    ]
                ]
            ],
            $result
        );
    }

    public function testFirstLevel()
    {
        $model = new stdClass();
        $model->Id = 10;
        $model->Name = 'Joao';
        $model->Object = new ModelGetter(20, 'JG');

        $object = Serialize::From($model);
        $object->withStopAtFirstLevel();
        $result = $object->toArray();

        $this->assertEquals(
            ["Id" => 10, "Name" => 'Joao', 'Object' => new ModelGetter(20, 'JG')],
            $result
        );

    }

    public function testFirstLevel_2()
    {
        $model = new stdClass();
        $model->Obj = [
            10,
            'Joao',
            new ModelGetter(20, 'JG')
        ];

        $object = Serialize::From($model);
        $object->withStopAtFirstLevel();
        $result = $object->toArray();

        $this->assertEquals(
            ["Obj" => [10, 'Joao', new ModelGetter(20, 'JG')]],
            $result
        );
    }

    public function testDoNotParseClass()
    {
        $model = new stdClass();
        $model->Id = 10;
        $model->Name = 'Joao';
        $model->Object1 = new ModelGetter(20, 'JG');
        $model->Object2 = new ModelPublic(10, 'JG2');

        $object = Serialize::From($model);
        $object->withDoNotParse([
            ModelPublic::class
        ]);
        $result = $object->toArray();

        $this->assertEquals(
            [
                "Id" => 10,
                "Name" => 'Joao',
                'Object1' => ['Id' => 20, 'Name' => 'JG'],
                'Object2' => new ModelPublic(10, 'JG2')
            ],
            $result
        );

        $object2 = Serialize::From($model);
        $object2->withDoNotParse([
            ModelPublic::class,
            ModelGetter::class
        ]);
        $result = $object2->toArray();

        $this->assertEquals(
            [
                "Id" => 10,
                "Name" => 'Joao',
                'Object1' => new ModelGetter(20, 'JG'),
                'Object2' => new ModelPublic(10, 'JG2')
            ],
            $result
        );

        $this->assertEquals('{"Id":10,"Name":"Joao","Object1":{},"Object2":{"Id":10,"Name":"JG2"}}', $object2->toJson());
    }

    public function testDoNotParseClass_2()
    {
        $model = new stdClass();
        $model->Obj = [
            10,
            'Joao',
            ["Other" => new ModelGetter(20, 'JG')]
        ];
        $model->Obj2 = [
            20,
            'Gilberto',
            new ModelPublic(10, 'JG2')
        ];

        $object = Serialize::From($model);
        $object->withDoNotParse([
            ModelGetter::class
        ]);
        $result = $object->toArray();

        $this->assertEquals(
            [
                "Obj" => [
                    10,
                    'Joao',
                    ["Other" => new ModelGetter(20, 'JG')]
                ],
                "Obj2" => [
                    20,
                    'Gilberto',
                    ['Id' => '10', 'Name' => 'JG2']
                ]
            ],
            $result
        );
    }

    public function testModelWithFakeProperty()
    {
        $model = new ModelForceProperty();

        $object = Serialize::From($model);
        $result = $object->toArray();

        $this->assertEquals(
            [
                "fakeProp" => 20
            ],
            $result
        );
    }

    public function testSerializeJson()
    {
        $this->assertEquals(["a"=>1, "b"=>2], Serialize::fromJson('{"a": 1, "b": 2}')->toArray());
    }

    public function testSerializeYaml()
    {
        $yaml = file_get_contents(__DIR__ . "/yamlserialize.yml");

        $this->assertEquals(
            [ "name"=> "test",
                "values" => [
                    ["a"=>1, "b"=>2],
                    ["c"=>3, "d"=>4]
                ]
            ],
            Serialize::fromYaml($yaml)->toArray()
        );
    }

    public function testSerializePhp()
    {
        $this->assertEquals(["a"=>1, "b"=>2], Serialize::fromPhpSerialize(serialize(["a" => 1, "b" => 2]))->toArray());
    }

    public function testSerializePhpClass()
    {
        $model = new ModelList();
        $model->addItem(new ModelGetter(10, 'Joao'));
        $model->addItem(new ModelGetter(20, 'JG'));

        $serialize = serialize($model);

        $expectedArray = [
            "collection" => [
                ['Id' => 10, 'Name' => 'Joao'],
                ['Id' => 20, 'Name' => 'JG'],
            ]
        ];

        $array = Serialize::fromPhpSerialize($serialize)->toArray();
        $returnSerialize = Serialize::from($model)->toPhpSerialize();

        $this->assertEquals($expectedArray, $array);
        $this->assertEquals($serialize, $returnSerialize);
    }

    public function testToYaml()
    {
        $model = new ModelList();
        $model->addItem(new ModelGetter(10, 'Joao'));
        $model->addItem(new ModelGetter(20, 'JG'));

        $expectedYaml = "collection:\n  - { Id: 10, Name: Joao }\n  - { Id: 20, Name: JG }\n";

        $this->assertEquals($expectedYaml, Serialize::from($model)->toYaml());
    }

    public function testToJson()
    {
        $model = new ModelList();
        $model->addItem(new ModelGetter(10, 'Joao'));
        $model->addItem(new ModelGetter(20, 'JG'));

        $expectedJson = '{"collection":[{"Id":10,"Name":"Joao"},{"Id":20,"Name":"JG"}]}';

        $this->assertEquals($expectedJson, Serialize::from($model)->toJson());
    }

    public function testToXml()
    {
        $model = new ModelList();
        $model->addItem(new ModelGetter(10, 'Joao'));
        $model->addItem(new ModelGetter(20, 'JG'));

        $expectedXml = "<?xml version=\"1.0\"?>\n<root><collection><item><Id>10</Id><Name>Joao</Name></item><item><Id>20</Id><Name>JG</Name></item></collection></root>\n";

        $this->assertEquals($expectedXml, Serialize::from($model)->toXml());
    }

    public function testToPlainText()
    {
        $model = new ModelList();
        $model->addItem(new ModelGetter(10, 'Joao'));
        $model->addItem(new ModelGetter(20, 'JG'));

        $expectedText = "10\nJoao\n\n20\nJG\n\n\n";

        $this->assertEquals($expectedText, Serialize::from($model)->toPlainText());
    }

    public function testIgnoreProperties()
    {
        $model = new stdClass();
        $model->Id = 10;
        $model->Name = 'Joao';
        $model->Object1 = new ModelGetter(20, 'JG');
        $model->Object2 = new ModelPublic(10, 'JG2');

        $serializerObject = Serialize::from($model);
        $serializerObject->withIgnoreProperties([
            "Id",
            'Object2'
        ]);
        $result = $serializerObject->toArray();

        $this->assertEquals(
            [
                "Name" => 'Joao',
                'Object1' => ['Name' => 'JG'],
            ],
            $result
        );
    }
}
