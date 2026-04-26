<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelpController extends Controller
{
    //
    public function customers(){
        $questions = [
			[
				"title" => "¿Como hago mi pedido?",
				"video_url" => "https://www.youtube.com/watch?v=4VR-6AS0-l4&list=RD4VR-6AS0-l4&start_radio=1",
				"isOpen" => false
			],
			[
				"title" => "¿Que es Kamgus Store?",
				"video_url" => "#",
				"isOpen" => false
			],
			[
				"title" => "¿Como veo mis servicios activos?",
				"video_url" => "#",
				"isOpen" => false
			],
			[
				"title" => "¿Como programar servicios?",
				"video_url" => "#",
				"isOpen" => false
			],
			[
				"title" => "¿Que diferencia tiene articulos de vehiculos?",
				"video_url" => "#",
				"isOpen" => false
			],  
		];
        return response()->json([
            "questions" => $questions,
        ]);
    }
}
