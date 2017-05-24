<?php
$PluginInfo['dashboardCRUD'] = [
    'Name' => 'Dashboard CRUD Helper',
    'Description' => 'Helper plugin which provides a class that can be used by plugin authors to implement simple CRUD actions on one table in the dashboard.',
    'Version' => '0.0.1',
    'RequiredApplications' => ['Vanilla' => '2.3'],
    'MobileFriendly' => true,
    'HasLocale' => true,
    'Author' => 'Robin Jurinka',
    'AuthorUrl' => 'http://vanillaforums.org/profile/r_j',
    'License' => 'MIT'
];

class DashboardCRUDPlugin extends Gdn_Plugin {
    public $tableName = '';
    public $tableSchema;
    public $primaryKey = '';
    public $actionLink = '';
    public $blacklist = [];

    /**
     * Init variables.
     *
     * @param string $tableName The name of the table. Required.
     * @param string $primaryKey The primary key of the table. Required.
     * @param string $actionLink The link to the plugins CRUD end point. Required.
     *
     * @return  void.
     */
    public function __construct($tableName, $primaryKey, $actionLink) {
        $this->tableName = $tableName;
        $this->primaryKey = $primaryKey;
        $this->actionLink = $actionLink;
    }

    public function index($sender, $args, $options) {
        $this->read($sender, $args, $options);
    }

    /**
     * [read description]
     * @param  [type] $sender  [description]
     * @param  [type] $args    [description]
     * @param array $options Allowed options are
     *                       Title: index page title.
     *                       Description: description to show on index page.
     *                       Blacklist: Columns to exclude from display
     *
     * @return void.
     */
    public function read($sender, $args, $options) {
        $sender->setData([
            'Title' => val('Title', $options, t('Show All')),
            'Description' => val('Description', $options, false),
            'Schema' => Gdn::sql()->fetchTableSchema($this->tableName),
            'Data' => Gdn::sql()->get($this->tableName)->resultArray(),
            'Blacklist' => val('Blacklist', $options, []),
            'PrimaryKey' => $this->primaryKey,
            'ActionLink' => $this->actionLink,
            'TransientKey' => Gdn::session()->transientKey()
        ]);

        $sender->render(val(
            'View',
            $options,
            $sender->fetchViewLocation('read', '', 'plugins/dashboardCRUD')
        ));
    }

    public function add($sender, $args, $options = []) {
        if ($sender->Form->authenticatedPostBack() === true) {
            $model = new Gdn_Model($this->tableName);
            $sender->Form->setModel($model);
            if ($sender->Form->save() !== false) {
                $sender->informMessage = t('Saved');
                redirect($this->actionLink);
            }
        }

        $sender->setData([
            'Title' => val('Title', $options, t('Add Item')),
            'Description' => val('Description', $options, false),
            'FormSchema' => $this->getFormSchema(val('Blacklist', $options))
        ]);

        $sender->render(val(
            'View',
            $options,
            $sender->fetchViewLocation('create', '', 'plugins/dashboardCRUD')
        ));
    }

    public function edit($sender, $args, $options = []) {
        if (!isset($options['Title'])) {
            $options['Title'] = t('Edit Item');
        }
        if (!isset($options['View'])) {
            // $options['View'] = $sender->fetchViewLocation('update', '', 'plugins/dashboardCRUD');
        }
        $row = Gdn::sql()->getWhere($this->tableName, [$this->primaryKey => $args[1]])->firstRow();
        $sender->Form->setData($row);
        $sender->Form->addHidden($this->primaryKey, $row->{$this->primaryKey}, true);
        $this->add($sender, $args, $options);
    }

    public function delete($sender, $args, $options) {
        if (Gdn::session()->validateTransientKey($sender->Request->get('tk'))) {
            Gdn::sql()->delete($this->tableName, [$this->primaryKey => $args[1]]);
        }
        redirect($this->actionLink);
    }

    private function getFormSchema($blacklist = []) {
        $tableSchema = Gdn::sql()->fetchTableSchema($this->tableName);
        $formSchema = [];
        foreach ($tableSchema as $column => $schema) {
            if ($column == $this->primaryKey || in_array($column, $blacklist)) {
                continue;
            }
            switch ($schema->Type) {
                case 'tinytext':
                case 'text':
                    $formSchema[$column]['Control'] = 'TextBox';
                    $formSchema[$column]['Options'] = ['class' => 'InputBox BigInput'];
                    break;
                case 'int':
                    $formSchema[$column]['Control'] = 'TextBox';
                    break;
                case 'tinyint':
                    $formSchema[$column]['Control'] = 'CheckBox';
                    break;
                case 'date':
                case 'datetime':
                    // $formSchema[$column]['Control'] = 'Date';
                    $formSchema[$column]['Control'] = 'TextBox';
                    break;
                case 'enum':
                    if (count($schema->Enum) <= 4) {
                        $formSchema[$column]['Control'] = 'RadioList';
                    } else {
                        $formSchema[$column]['Control'] = 'DropDown';
                    }
                    $formSchema[$column]['Items'] = array_combine($schema->Enum, $schema->Enum);
                    if (!$schema->AllowNull) {
                        $formSchema[$column]['Options'] = ['IncludeNull' => true];
                    }
                    break;
                case 'varchar':
                default:
                    $formSchema[$column]['Control'] = 'TextBox';
                    if ($schema->Length > 80) {
                        $formSchema[$column]['Options'] = ['MultiLine' => true];
                    } elseif ($schema->Length > 40) {
                        $formSchema[$column]['Options'] = ['class' => 'InputBox BigInput'];
                    }
                    break;
            }
        }
        return $formSchema;
    }
}
