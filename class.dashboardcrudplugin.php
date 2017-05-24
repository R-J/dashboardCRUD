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
    /** @var string The name of the table used in this plugin. */
    public $tableName = '';

    /** @var string The tables primary key. Defaults to TableNameID. */
    public $primaryKey = '';

    /** @var string The link to the index page. */
    public $indexLink = '';

    /**
     * Init variables.
     *
     * @param string $tableName The name of the table. Required.
     * @param string $indexLink The link to the plugins CRUD end point. Required.
     * @param string $primaryKey The primary key of the table. Defaults to "TableNameID".
     *
     * @return  void.
     */
    public function __construct($tableName, $indexLink, $primaryKey) {
        $this->tableName = $tableName;
        $this->indexLink = $indexLink;

        if (isset($primaryKey)) {
            $this->primaryKey = $primaryKey;
        } else {
            // Build a PK if none is set: "TableNameID".
            $this->primaryKey = ucfirst($tableName).'ID';
        }

    }

    /**
     * Show list of all entries in the table.
     *
     * Simple table with all rows from the database table. No pagination!
     *
     * @param SettingsController $sender Instance of the calling class.
     * @param array $args Url parameters.
     * @param array $options Allowed options are
     *                       Title: index page title.
     *                       Description: description to show on index page.
     *                       Blacklist: Columns to exclude from being displayed.
     *                       View: A file path to a custom view.
     *
     * @return void.
     */
    public function index($sender, $args, $options) {
        // Pass info to form.
        $sender->setData([
            'Title' => val('Title', $options, t('Show All')),
            'Description' => val('Description', $options, false),
            'Schema' => Gdn::sql()->fetchTableSchema($this->tableName),
            'Data' => Gdn::sql()->get($this->tableName)->resultArray(),
            'Blacklist' => val('Blacklist', $options, []),
            'PrimaryKey' => $this->primaryKey,
            'IndexLink' => $this->indexLink,
            'TransientKey' => Gdn::session()->transientKey()
        ]);

        // Render custom view if specified, else render default view for "Read"
        // action.
        $sender->render(val(
            'View',
            $options,
            $sender->fetchViewLocation('index', '', 'plugins/dashboardCRUD')
        ));
    }

    /**
     * Add new entries to the table.
     *
     * Shows form with best guessed form elements for entering new rows to
     * the table.
     *
     * @param SettingsController $sender Instance of the calling class.
     * @param array $args Url parameters.
     * @param array $options Allowed options are
     *                       Title: index page title.
     *                       Description: description to show on index page.
     *                       Blacklist: Columns to exclude from being displayed.
     *                       View: A file path to a custom view.
     *
     * @return void.
     */
    public function add($sender, $args, $options = []) {
        // Save only authenticated POST data.
        if ($sender->Form->authenticatedPostBack() === true) {
            // Create new model for table.
            $model = new Gdn_Model($this->tableName);
            $sender->Form->setModel($model);
            if ($sender->Form->save() !== false) {
                // Show success message and redirect to index (success message
                // will not be seen by user...)
                $sender->informMessage = t('Saved');
                redirect($this->indexLink);
            }
        }

        // Add form information.
        $sender->setData([
            'Title' => val('Title', $options, t('Add Item')),
            'Description' => val('Description', $options, false),
            'FormSchema' => $this->getFormSchema(val('Blacklist', $options))
        ]);

        // Render custom view if specified, else render default view for "Read"
        // action.
        $sender->render(val(
            'View',
            $options,
            $sender->fetchViewLocation('add', '', 'plugins/dashboardCRUD')
        ));
    }

    /**
     * Edit entries in the table.
     *
     * Shows form with best guessed form elements for editing rows of
     * the table.
     *
     * @param SettingsController $sender Instance of the calling class.
     * @param array $args Url parameters.
     * @param array $options Allowed options are
     *                       Title: index page title.
     *                       Description: description to show on index page.
     *                       Blacklist: Columns to exclude from being displayed.
     *                       View: A file path to a custom view.
     *
     * @return void.
     */
    public function edit($sender, $args, $options = []) {
        // Change title if no custom title has been chosen, anyway.
        if (!isset($options['Title'])) {
            $options['Title'] = t('Edit Item');
        }

        // Get table row and set it to form.
        $row = Gdn::sql()->getWhere($this->tableName, [$this->primaryKey => $args[1]])->firstRow();
        $sender->Form->setData($row);
        // Add PrimaryKey as hidden form element so that save action will be
        // recognized as UPDATE.
        $sender->Form->addHidden($this->primaryKey, $row->{$this->primaryKey}, true);

        // Reuse $this->add() method.
        $this->add($sender, $args, $options);
    }

    /**
     * Delete entry from the table.
     *
     * No user interaction. This is handled by the "PopConfirm" class in the
     * index view.
     *
     * @param SettingsController $sender Instance of the calling class.
     * @param array $args Url parameters.
     * @param array $options Not used by now. Maybe will be used for providing
     *                       custom view in future versions
     *
     * @return void.
     */
    public function delete($sender, $args, $options) {
        // Check for valid TransientKey before deleting.
        if (Gdn::session()->validateTransientKey($sender->Request->get('tk'))) {
            Gdn::sql()->delete($this->tableName, [$this->primaryKey => $args[1]]);
        }
        redirect($this->indexLink);
    }

    /**
     * Transform table schema to form schema.
     *
     * Creates a schema usable for the GDN_Form simple() method from a tables
     * schema.
     *
     * @param array $blacklist Columns to exclude from being displayed.
     *
     * @return array Form elements schema usable by GDN_Form->simple().
     */
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
