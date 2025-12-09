<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CaseModel;
use App\Models\Decision;
use OpenAI;

class AIDecisionController extends Controller
{
    public function index()
    {
        $cases = CaseModel::with('decision')->latest()->get();
        return view('dashboard', compact('cases'));
    }

  public function analyze(Request $request)
{
    $case = CaseModel::create($request->all());

    try {
        $client = \OpenAI::client(env('OPENAI_API_KEY'));

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an Islamic Finance Shariah compliance auditor.'],
                ['role' => 'user', 'content' =>
                    "Analyze this Islamic finance case:\n".
                    "Financing Type: {$case->financing_type}\n".
                    "Amount: {$case->amount}\n".
                    "Details: {$case->description}\n".
                    "Explain decision clearly and provide Shariah reasoning."
                ],
            ],
        ]);

        $answer = $response->choices[0]->message->content;

        Decision::create([
            'case_id' => $case->id, // ✅ link it correctly
            'ai_decision' => $answer,
            'ai_explanation' => $answer,
        ]);

        return redirect()->back()->with('success', 'AI decision saved.');

    } catch (\OpenAI\Exceptions\RateLimitException $e) {
        return redirect()->back()->with('error', 'Too many requests. Try again in 30 seconds.');
    }
}


}

