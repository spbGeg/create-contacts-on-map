<?php

namespace VadimRomanov;

use http\Exception;
use React\Promise\Promise;
use VadimRomanov\Migrations\IBlockMigration;
use Bitrix\Main\Loader;
use Bitrix\Iblock;
use Bitrix\Main\SystemException;
use Recoil\ReferenceKernel\ReferenceKernel;

class FabricContacts extends HelperIblock
{
    /**
     * data for create iblock
     * @var array[]
     */
    private $iblockData = [
        'CODE' => 'contacts',
        'NAME' => 'Контакты'
    ];

    /**
     * data for create type iblock
     * @var array[]
     */
    private $iblockType = [
        'ID' => 'content',
        'NAME' => 'Контент'
    ];

    /**
     * Property for iblock contacts
     *
     * @var array[]
     */
    protected $iblockFields = [
        'CITY' => ['N', 'S',
            [
                'EDIT_FORM_LABEL' => 'Город',
                'IU_ADD_IN_LIST' => true,
            ],
        ],
        'PHONE' => ['N', 'S',
            [
                'EDIT_FORM_LABEL' => 'Телефон',
                'IU_ADD_IN_LIST' => true,
            ],
        ],
        'EMAIL' => ['N', 'S',
            [
                'EDIT_FORM_LABEL' => 'Email',
                'IU_ADD_IN_LIST' => true,
            ],
        ],
        'COORDS' => ['N', 'S:map_yandex',
            [
                'EDIT_FORM_LABEL' => 'Координаты',
                'IU_ADD_IN_LIST' => true,
            ],
        ],
    ];
    /**
     * Demo elemements
     * @var array[]
     */
    private $arContactElements = [
        [
            'NAME' => 'Офис 1',
            'PROPERTY_VALUES' => [
                'CITY' => 'Санкт-Петербург',
                'PHONE' => '78123353684',
                'EMAIL' => 'nana@mail.ru',
                'COORDS' => '59.858492, 30.341346',
            ]
        ],
        [
            'NAME' => 'Офис 2',
            'PROPERTY_VALUES' => [
                'CITY' => 'Москва',
                'PHONE' => '74953353684',
                'EMAIL' => 'fdfana@mail.ru',
                'COORDS' => '55.762237, 37.496208',
            ]
        ],
        [
            'NAME' => 'Офис 3',
            'PROPERTY_VALUES' => [
                'CITY' => 'Солнечногорск',
                'PHONE' => '74962643909',
                'EMAIL' => 'ooona@mail.ru',
                'COORDS' => '56.179956, 34.967648',
            ]
        ],
        [
            'NAME' => 'Офис 4',
            'PROPERTY_VALUES' => [
                'CITY' => 'Солнечногорск',
                'PHONE' => '74962643909',
                'EMAIL' => 'ooona@mail.ru',
                'COORDS' => '56.179956, 36.967648',
            ]
        ],
        [
            'NAME' => 'Офис5',
            'PROPERTY_VALUES' => [
                'CITY' => 'Клин',
                'PHONE' => '74962643909',
                'EMAIL' => 'dsdfa@mail.ru',
                'COORDS' => '56.331595, 36.728711',
            ]
        ],
        [
            'NAME' => 'Офис 6',
            'PROPERTY_VALUES' => [
                'CITY' => 'село Рогачёво',
                'PHONE' => '74962643909',
                'EMAIL' => 'o33na@mail.ru',
                'COORDS' => '56.433509, 37.158559',
            ]
        ]
    ];


    /**
     * construct method for FabricContacts class
     *
     * @throws SystemException
     * @throws \Bitrix\Main\LoaderException
     */
    public function __construct()
    {
        if (!Loader::IncludeModule("iblock")) {
            throw new SystemException('iblock not installed');
        }
    }

    /**
     * Method for check exist type iblock, if no exist create it
     *
     * @param $result
     * @return array
     * @throws SystemException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     */
    private function checkAndCreateTypeIblock($result)
    {
        //check isset iblock type
        $resTypeIblock = $this->issetTypeIblock($this->iblockType['ID']);

        //create typeIblock
        if (empty($resTypeIblock)) {

            $resTypeIblock = $this->addTypeIblock($this->iblockType);

           Tools::logFile($resTypeIblock, '$resTypeIblock after create create typeIblock');

            if ($resTypeIblock['STATUS'] == 'success') {
                $result['MSG'][] = 'Тип инфоблока Контент успешно добавлен';
                $result['STATUS'] = 'resolve';
            } else {
                $result['STATUS'] = 'fail';
                throw new \Exception("Ошибка добавления iblockType " . $resTypeIblock);
            }
        }else{
            $result['STATUS'] = 'resolve';
        }


        if (empty($resTypeIblock)) {
            throw new \Exception("Тип инфоблока не удалось добавить");
        }

        return $result;
    }

    /**
     * Method for check exist  iblock, if no exist create it
     *
     * @param array $result
     * @return array
     * @throws \Exception
     */
    private function checkAndCreateIblock(array $result): array
    {
        $this->iblockData['ID'] = $this->findIblock($this->iblockType['ID'], $this->iblockData['CODE']);
        //create iblock
        if (empty($this->iblockData['ID'])) {

                $result =  $this->addIblock($this->iblockType['ID'], $this->iblockData['CODE'], $this->iblockData['NAME']);
            if (!isset($resIblock['ERROR'])) {
                $result['MSG'][] = 'Инфоблок Контакты успешно добавлен';
                $result['STATUS'] = 'iblockCreated';
                $this->iblockData['ID'] = $result['IBLOCK']['ID'];
            } else {
                $result['STATUS'] = 'fail';
                throw new \Exception("Ошибка добавления инфоблока " . $resIblock['ERROR']);
            }
        }

        if (empty($this->iblockData['ID'])) {
            throw new \Exception("Инфоблок не удалось добавить");
        } else {
            return $result;
        }
    }

    /**
     * Method for check exist property iblock, if no exist create them
     *
     * @param array $result
     * @return array
     */
    private function checkAndCreatePropIblock(array $result) :array
    {

        //create property if empty
        $propertyCodeIblock = array_keys($this->iblockFields);
        $issetProp = $this->isExistPropertyFields($this->iblockData['ID'], $propertyCodeIblock[0]);
        if (empty($issetProp)) {
            $result['MSG'][] = 'Свойства инфоблока Контакты успешно добавлены';
            $this->addPropertyFields($this->iblockData['ID'], $this->iblockFields);
        }
        return $result;
    }

    /**
     * Method for check exist demo elements iblock, if no exist create them
     *
     * @param array $result
     * @return array
     * @throws \Exception
     */
    private function checkAndCreateElementsIblock(array $result) : array
    {
        if (!$this->isExistElements($this->iblockData['CODE'])) {
            foreach ($this->arContactElements as $item) {
                $resAddEl = $this->addElementIblock($this->iblockData['ID'], $item);
                if ($resAddEl['ID']) {
                    $result['MSG'][] = $resAddEl['MSG'];
                }
                if (isset($resAddEl['ERROR'])) {

                    $result['STATUS'] = 'fail';
                    throw new \Exception("Элемент не добавлен " . $resAddEl['ERROR']);

                }
            }
            if (empty($error)) {
                $result['STATUS'] = 'allCreated';
            }
        } else {
            $result['STATUS'] = 'allCreated';
        }
        return $result;
    }

    /**
     * This method check exist typeIblock, iblock, it`s prop, it`s demo elements,
     * if no exist create them
     * @return array
     */
    public function create(): array
    {
        $error = [];
        $msg = [];
        $result = [];

        try {
                $result =  $this->checkAndCreateTypeIblock($result);
                $result = $this->checkAndCreateIblock($result);
                $result =  $this->checkAndCreatePropIblock($result);
                $result =  $this->checkAndCreateElementsIblock($result);

        } catch (\Exception $e) {
            $result['ERROR'] = $e->getMessage();
        }

        return $result;
    }
}