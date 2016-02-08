<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use App\Lib\Distribution\PriorityIndex;
use App\Lib\Distribution\Distribution;
use Cake\Event\Event;

/**
 * Processes Controller
 *
 * @property \App\Model\Table\ProcessesTable $Processes
 */
class ProcessesController extends AppController
{
	public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Auth->allow(['prototypeDistribute']);

        $redirectToHome = ['requestAccount', 'login'];
        $authUser = isset($this->request->Session()->read('Auth')['User']) ? true : false;
        if($authUser && in_array($this->request->params['action'], $redirectToHome)) {
            return $this->redirect('/');
        }
    }

    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $this->paginate = [
            'contain' => []
        ];
        $this->set('processes', $this->paginate($this->Processes));
        $this->set('_serialize', ['processes']);
    }

    /**
     * View method
     *
     * @param string|null $id Process id.
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function view($id = null)
    {
        $process = $this->Processes->get($id, [
            'contain' => [
                'Clazzes', 'Clazzes.Processes'
            ]
        ]);
        $this->set('process', $process);
        $this->set('_serialize', ['process']);
    }

    /**
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $process = $this->Processes->newEntity();
        if ($this->request->is('post')) {
            $process = $this->Processes->patchEntity($process, $this->request->data);
            $process->status = 'OPEN';
            if ($this->Processes->save($process)) {
                $this->Flash->success(__('O processo foi salvo.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('Não foi possível salvar o processo. Tente novamente.'));
            }
        }
        $this->set(compact('process'));
        $this->set('_serialize', ['process']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Process id.
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $process = $this->Processes->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $process = $this->Processes->patchEntity($process, $this->request->data);
            if ($this->Processes->save($process)) {
                $this->Flash->success(__('O processo foi salvo!'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('Não foi possível salvar o processo. Tente novamente.'));
            }
        }
        $this->set(compact('process'));
        $this->set('_serialize', ['process']);
    }

    /**
     * Cancel method
     *
     * @param string|null $id Process id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function cancel($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $process = $this->Processes->get($id);
        $process->status = 'CANCELLED';
        if ($this->Processes->save($process)) {
            $this->Flash->success(__('O processo foi cancelado!'));
        } else {
            $this->Flash->error(__('Não foi possível cancelar o processo. Tente novamente.'));
        }
        return $this->redirect(['action' => 'index']);
    }

	public function prototypeDistribute(){
		//http://localhost:8765/processes/prototype-distribute
		$this->autoRender = false;
		$this->response->type('json');
		$clazzes = $this->Processes->Clazzes->getAllClazzesWithSubjctsTeachers();
		$teachers = TableRegistry::get('Teachers')->getAllTeachersWithKnowledge();
		$clazzes = Distribution::generateDistribution($clazzes, $teachers);
		$this->Processes->Clazzes->setTeachersAllClazzes($clazzes);
		$clazzes = $this->Processes->Clazzes->getAllClazzesWithSubjctsTeachers();
		$this->response->body(json_encode($clazzes, JSON_PRETTY_PRINT));
	}
	
	public function distribute(){
        $clazzes = $this->Processes->Clazzes->find('all')->contain(['Teachers.Users', 'Locals', 'Subjects']);
        $clazzes = $this->paginate($clazzes);
        $this->set('clazzes', $clazzes);
	}
	
	public function simulate(){
        $clazzes = $this->Processes->Clazzes->find('all')->contain(['Teachers.Users', 'Locals', 'Subjects']);
        $clazzes = $this->paginate($clazzes);
        $this->set('clazzes', $clazzes);
	}
	
	public function revert(){
        $this->paginate = [
            'contain' => []
        ];
        $this->set('processes', $this->paginate($this->Processes));
        $this->set('_serialize', ['processes']);
	}

    
    public function cloneProcess($id = null)
    {
        $clonedProcess = $this->Processes->get($id, [
            'contain' => ['Clazzes']
        ]);

        if ($this->request->is('post')) {
            $process = $this->Processes->patchEntity($process, $this->request->data);
            $process->status = 'OPEN';
            if ($this->Processes->save($process)) {
                $this->Flash->success(__('O processo foi clonado.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('Não foi possível clonar o processo. Tente novamente.'));
            }
        } else {
            $process = $this->Processes->newEntity();
            $process->name = $clonedProcess->name;
            $process->initial_date = $clonedProcess->initial_date;
            $process->teacher_intent_date = $clonedProcess->teacher_intent_date;
            $process->primary_distribution_date = $clonedProcess->primary_distribution_date;
            $process->substitute_intent_date = $clonedProcess->substitute_intent_date;
            $process->secondary_distribution_date = $clonedProcess->secondary_distribution_date;
            $process->final_date = $clonedProcess->final_date;
            foreach ($clonedProcess->clazzes as $clonedClazze) {
                $clazze = $this->Clazzes->newEntity();
                $clazze->name = $clonedClazze->name;
                $clazze->$vacancies = $clonedClazze->name;
                $clazze->$subject = $clonedClazze->name;
                $clazze->$schedule = $clonedClazze->name;
                $clazze->$local = $clonedClazze->name;
                $clazze->$process = $process;
                $clazze->$teachers = $clonedClazze->$teachers;

                $process->clazzes->push($clazze);
            }
            $process->clazzes = debug($clonedProcess->clazzes);
        }

        $this->set(compact('$process'));
        $this->set('_serialize', ['$process']);
    }
    
}
