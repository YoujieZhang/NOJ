<?php

namespace App\Http\Controllers\Ajax;

use App\Models\ProblemModel;
use App\Models\SubmissionModel;
use App\Models\ResponseModel;
use App\Models\CompilerModel;
use App\Http\Controllers\VirtualJudge\Submit;
use App\Http\Controllers\VirtualJudge\Judge;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\VirtualCrawler\Crawler;
use App\Jobs\ProcessSubmission;
use Illuminate\Support\Facades\Validator;
use Auth;

class ProblemController extends Controller
{
    /**
     * The Ajax Problem Solution Submit.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function submitSolution(Request $request)
    {
        $problemModel=new ProblemModel();
        $submissionModel=new SubmissionModel();
        $compilerModel=new CompilerModel();

        $all_data=$request->all();

        $validator=Validator::make($all_data, [
            'solution' => 'required|string|max:65535',
        ]);

        if ($validator->fails()) {
            return ResponseModel::err(3002);
        }

        if ($problemModel->isBlocked($all_data["pid"], isset($all_data["contest"]) ? $all_data["contest"] : null)) {
            return header("HTTP/1.1 403 Forbidden");
        }

        $lang=$compilerModel->detail($all_data["coid"]);

        $sid=$submissionModel->insert([
            'time'=>'0',
            'verdict'=>'Pending',
            'solution'=>$all_data["solution"],
            'language'=>$lang['display_name'],
            'submission_date'=>time(),
            'memory'=>'0',
            'uid'=>Auth::user()->id,
            'pid'=>$all_data["pid"],
            'remote_id'=>'',
            'coid'=>$all_data["coid"],
            'cid'=>isset($all_data["contest"]) ? $all_data["contest"] : null,
            'jid'=>null,
            'score'=>0
        ]);

        $all_data["sid"]=$sid;
        $all_data["oj"]=$problemModel->ocode($all_data["pid"]);
        $all_data["lang"]=$lang['lcode'];
        dispatch(new ProcessSubmission($all_data))->onQueue($all_data["oj"]);

        return ResponseModel::success(200, null, [
            "sid"=>$sid
        ]);
    }
    /**
     * The Ajax Problem Solution Submit.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function problemExists(Request $request)
    {
        $all_data=$request->all();
        $problemModel=new ProblemModel();
        $pcode=$problemModel->existPCode($all_data["pcode"]);
        if ($pcode) {
            return ResponseModel::success(200, null, [
                "pcode"=>$pcode
            ]);
        } else {
            return ResponseModel::err(3001);
        }
    }
    /**
     * The Ajax Problem Judge.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function judgeStatus(Request $request)
    {
        // [ToDo] can only query personal judge info.
        $all_data=$request->all();
        $submission=new SubmissionModel();
        $status=$submission->getJudgeStatus($all_data["sid"]);
        return ResponseModel::success(200, null, $status);
    }

    /**
     * The Ajax Problem Manual Judge.
     * [Notice] THIS FUNCTION IS FOR TEST ONLY
     * SHALL BE STRICTLY FORBIDDEN UNDER PRODUCTION ENVIRONMENT.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function manualJudge(Request $request)
    {
        if (Auth::user()->id!=1) {
            return ResponseModel::err(2001);
        }

        $vj_judge=new Judge();

        return ResponseModel::success(200, null, $vj_judge->ret);
    }

    /**
     * Get the Submit History.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function submitHistory(Request $request)
    {
        $all_data=$request->all();
        $submission=new SubmissionModel();
        if (isset($all_data["cid"])) {
            $history=$submission->getProblemSubmission($all_data["pid"], Auth::user()->id, $all_data["cid"]);
        } else {
            $history=$submission->getProblemSubmission($all_data["pid"], Auth::user()->id);
        }

        return ResponseModel::success(200, null, ["history"=>$history]);
    }

    /**
     * Crawler Ajax Control.
     * [Notice] THIS FUNCTION IS FOR TEST ONLY
     * SHALL BE STRICTLY FORBIDDEN UNDER PRODUCTION ENVIRONMENT.
     *
     * @param Request $request web request
     *
     * @return Response
     */
    public function crawler(Request $request)
    {
        if (Auth::user()->id!=1) {
            return ResponseModel::err(2001);
        }

        $all_data=$request->all();

        new Crawler($all_data["name"], $all_data["action"], $all_data["con"], $all_data["cached"]);

        return ResponseModel::success(200);
    }
}
