<?php

/**
 * Class TK_GProject
 */
class TK_GProject
{
    /**
     * @const string slug
     */
    const slug = 'tk_project';
    /**
     * @var integer|null
     */
    protected $project_id;
    /**
     * @var bool
     */
    protected $is_project = false;
    /**
     * @var int
     */
    protected $project_type;
    /**
     * @var int
     */
    protected $project_visibility;

    /**
     * TK_GProject constructor.
     * @param null $post_id
     */
    public function __construct($post_id = null)
    {
        if (isset($post_id)) {
            $res = get_post($post_id);

            if (is_object($res)) {
                $this->project_id = $res->ID;
                $this->is_project = $res->post_type == self::slug ? true : false;

                if ($this->is_project) {
                    $this->project_type = intval(get_post_meta($this->project_id, 'ptype', true));
                    $this->project_visibility = intval(get_post_meta($this->project_id, 'visiblity', true));
                }
            }
        }
    }

    /**
     * Return HTML code of Project Archive page
     *
     * @return string
     */
    public static function getPageHtml()
    {
        $html = '';

        return $html;
    }

    /**
     * Return array with Project Settings fields
     *
     * @return array
     */
    public static function getProjectFields()
    {
        return array(
            array(
                'label' => _x('Type', 'Project Settings', 'tkgp'),
                'desc' => _x('Type of this project.', 'Project Settings', 'tkgp'),
                'id' => 'ptype',
                'type' => 'radio',
                'options' => array(
                    array(
                        'label' => _x('Private', 'Project Settings', 'tkgp'),
                        'value' => 0
                    ),
                    array(
                        'label' => _x('Working', 'Project Settings Type', 'tkgp'),
                        'value' => 1
                    ),
                    array(
                        'label' => _x('Members only', 'Project Settings Type', 'tkgp'),
                        'value' => 2
                    ),
                    array(
                        'label' => _x('Public', 'Project Settings Type', 'tkgp'),
                        'value' => 3
                    )
                )
            ),
            array(
                'label' => _x('Project Manager', 'Project Settings', 'tkgp'),
                'desc' => _x('Manager with full access to settings this Project.', 'Project Settings', 'tkgp'),
                'id' => 'manager',
                'type' => 'select_user',
                'options' => null
            ),
            array(
                'label' => _x('Working group', 'Project Settings', 'tkgp'),
                'desc' => _x('Group to which the project was created.', 'Project Settings', 'tkgp'),
                'id' => 'group',
                'type' => 'select_group',
                'options' => null
            ),
            array(
                'label' => _x('Visibility', 'Project Settings', 'tkgp'),
                'desc' => _x('Visibility for the categories of users.', 'Project Settings', 'tkgp'),
                'id' => 'visiblity',
                'type' => 'select',
                'properties' => array('size' => '1'),
                'options' => array(
                    array(
                        'label' => _x('Public', 'Project Settings', 'tkgp'),
                        'value' => 0
                    ),
                    array(
                        'label' => _x('Registered', 'Project Settings', 'tkgp'),
                        'value' => 1
                    ),
                    array(
                        'label' => _x('Members only', 'Project Settings', 'tkgp'),
                        'value' => 2
                    ),
                    array(
                        'label' => _x('Private', 'Project Settings', 'tkgp'),
                        'value' => 3
                    )
                )
            ),
        );
    }

    /**
     * @todo вынести в отдельный класс
     * Return TRUE when User by $user_id has Administrator, else FALSE.
     *
     * @param int $user_id
     * @return bool
     */
    protected static function userIsAdmin($user_id)
    {
        $res = false;

        $user_data = get_user_by('ID', $user_id);

        if ($user_data) {
            $roles_caps = $user_data->get_role_caps();
            $res = array_key_exists('administrator', $roles_caps) ? (boolean)$roles_caps['administrator'] : false;
        }

        return $res;
    }

    /**
     * @param bool $show_display_name
     * @return array|null
     */
    public function getManagers($show_display_name = false)
    {
        if (!$this->is_project) {
            return null;
        }

        $post = get_post($this->project_id);
        $managers = get_post_meta($this->project_id, 'manager', true);

        if (empty($managers)) {
            $managers[] = $post->post_author;
        }

        if (!is_array($managers)) {
            $managers[] = $managers;
        }

        if ($show_display_name) {
            for ($i = 0; $i < count($managers); ++$i) {
                $managers[$i] = array(
                    'id' => $managers[$i],
                    'display_name' => get_user_by('ID', $managers[$i])->display_name
                );
            }
        }

        return $managers;
    }

    /**
     * @param bool $show_display_name
     * @return array|null
     */
    public function getMembers($show_display_name = false)
    {
        if (!$this->is_project) {
            return null;
        }

        $post = get_post($this->project_id);

        $members = get_post_meta($this->project_id, 'member', true);

        if (!is_array($members)) {
            $members[] = $members;
        }

        if ($show_display_name) {
            for ($i = 0; $i < count($members); ++$i) {
                $members[$i] = array(
                    'id' => $members[$i],
                    'display_name' => get_user_by('ID', $members[$i])->display_name
                );
            }
        }

        return $members;
    }

    /**
     * @param bool $show_display_name
     * @return bool
     */
    public function getGroups($show_display_name = false)
    {
        return false;
    }

    /**
     * Update links Projects and Managers
     */
    public function updateProjectLinks()
    {

    }

    /**
     * Return HTML code of Project Post
     *
     * @return string
     */
    public function getProjectContent()
    {
        $html = '';

        if ($this->isValid()) {
            $post = get_post($this->project_id);
            $html .= $post->post_content;

            if (TK_GVote::exists($post->ID)) {
                $vote = new TK_GVote($post->ID);

                $user_id = get_current_user_id();
                $caps = $this->userCan($user_id);
                $html .= $vote->getResultVoteHtml($caps['vote'], !is_single($post->ID), !$caps['revote']);
            }
        }

        return $html;
    }

    /**
     * Check user Capabilities for this Project. Return an empty array when the user has no rights.
     *
     * @param int $user_id
     * @param array $caps Array('cap_name1', 'cap_name2', ...)
     * @return array
     */
    public function userCan($user_id, $caps = array())
    {
        $out_caps = array();

        if ($this->isValid()) {
            if (empty($caps)) {
                $caps = array('read', 'edit', 'work', 'vote', 'revote');
            }

            /*$p_type = intval(get_post_meta($this->project_id, 'ptype', true));
            $p_visiblity = intval(get_post_meta($this->project_id, 'visiblity', true));*/

            foreach ($caps as $cap) {
                $access = false;

                switch ($cap) {
                    case 'read':
                        $access = $this->userCanRead($user_id);
                        break;

                    case 'edit':
                        $access = $this->userCanEdit($user_id);
                        break;

                    case 'work':
                        $access = $this->userCanWork($user_id);
                        break;

                    case 'vote':
                        $access = $this->userCanVote($user_id);
                        break;

                    case 'revote':
                        $access = $this->userCanRevote($user_id);
                        break;

                    default:
                        break;
                }

                $out_caps[$cap] = $access;
            }
        }

        return $out_caps;
    }

    /**
     * Return TRUE when user can read project else FALSE
     *
     * @param int $user_id
     * @return bool
     */
    public function userCanRead($user_id)
    {
        $access = false;

        switch ($this->project_visibility) {
            case 0:
                $access = true;
                break;
            case 1:
                $user_data = get_user_by('ID', $user_id);
                $access = $user_data === false ? false : true;
                break;
            case 2:
            case 3:
            $members = $this->getManagers();
            $access = array_search($user_id, $members) === false && !$this->userIsAdmin($user_id) ? false : true;
                break;
            default:
                break;
        }


        return $access;
    }

    /**
     * Return TRUE when user can edit project else FALSE
     *
     * @param int $user_id
     * @return bool
     */
    public function userCanEdit($user_id)
    {
        $post = get_post($this->project_id);
        /**
         * @todo используй скобки для группироки
         */
        $access = (array_search($user_id, $this->getManagers(), true) !== false
            || $user_id === $post->post_author
            || is_super_admin($user_id)) ? true : false;

        return $access;
    }

    /**
     * Return TRUE when user can work project esle FALSE
     *
     * @param int $user_id
     * @return bool
     */
    public function userCanWork($user_id)
    {
        $can_edit = $this->userCanEdit($user_id);
        return $can_edit || array_search($user_id, $this->getMembers()) !== false;
    }

    /**
     * Return TRUE when user can vote project ELSE
     *
     * @param int $user_id
     * @return bool
     */
    public function userCanVote($user_id)
    {

        switch ($this->project_type) {
            case 0:
                $access = false;
                break;
            case 3:
                $access = get_userdata($user_id) !== false;
                break;
            default:
                $managers = $this->getManagers();
                $members = $this->getMembers();
                $all_members = !empty($members) ? array_merge($managers, $members) : $managers;
                $access = array_search($user_id, $all_members) !== false;
                break;
        }

        return $access;
    }

    /**
     * Return TRUE when user can revote project
     *
     * @param int $user_id
     * @return bool
     */
    public function userCanRevote($user_id)
    {
        $can_vote = $this->userCanVote($user_id);
        $vote = new TK_GVote($this->project_id);

        return ($can_vote && !$vote->userCanVote($user_id));
    }

    /**
     * Return TRUE where Project exist, else FALSE
     *
     * @return bool
     */
    public function isValid()
    {
        return isset($this->project_id);
    }
}

;

?>