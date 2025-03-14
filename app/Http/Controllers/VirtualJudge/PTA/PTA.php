<?php
namespace App\Http\Controllers\VirtualJudge\PTA;

use App\Http\Controllers\VirtualJudge\Curl;
use App\Models\JudgerModel;
use App\Models\ProblemModel;
use App\Models\ResponseModel;
use App\Models\SubmissionModel;
use App\Models\CompilerModel;
use Illuminate\Support\Facades\Validator;
use Requests;

class PTA extends Curl
{
    protected $sub;
    public $post_data=[];

    public function __construct(& $sub, $all_data)
    {
        $this->sub=& $sub;
        $this->post_data=$all_data;
    }

    private function ojLogin()
    {
        // F**k capcha
    }

    private function submitSolution()
    {
        $pid=$this->post_data['iid'];

        sleep(1);
        $response=$this->grab_page("https://pintia.cn/api/problem-sets/{$this->post_data['cid']}/exams", 'pta');

        if (strpos($response, 'PROBLEM_SET_NOT_FOUND')!==false) {
            header('HTTP/1.1 404 Not Found');
            die();
        }
        $generalDetails=json_decode($response, true);
        $examId=$generalDetails['exam']['id'];

        $params=[
            'details' => [
                [
                    'problemSetProblemId' => $this->post_data['iid'],
                    'programmingSubmissionDetail' => [
                        'compiler' => $this->post_data['lang'],
                        'program' => $this->post_data["solution"]
                    ]
                ]
            ],
            'problemType' => 'PROGRAMMING'
        ];

        $response=$this->post_data("https://pintia.cn/api/problem-sets/$examId/submissions?exam_id=".$examId, $params, 'pta', true, false, false, true);
        $ret=json_decode($response, true);
        if (isset($ret['submissionId'])) {
            $this->sub['remote_id']=$examId.'|'.$ret['submissionId'];
        } else {
            $this->sub['verdict']='Submission Error';
        }
    }

    public function submit()
    {
        $this->sub['verdict']="System Error";
        return;

        $validator=Validator::make($this->post_data, [
            'pid' => 'required|integer',
            'cid' => 'required|integer',
            'coid' => 'required|integer',
            'iid' => 'required|integer',
            'solution' => 'required',
        ]);

        if ($validator->fails()) {
            $this->sub['verdict']="System Error";
            return;
        }

        $this->ojLogin();
        $this->submitSolution();
    }
}
