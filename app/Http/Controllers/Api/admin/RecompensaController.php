<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Action;
use App\Http\Controllers\Api\ResponseController;
use App\Http\Controllers\Api\Type;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Admin\Recompensa\RecompensaStoreRequest;
use App\Http\Requests\API\Admin\Recompensa\RecompensaUpdateRequest;
use App\LogicLive\Config\Configuracao;
use App\LogicLive\Modulos\Recompensa as ModulosRecompensa;
use App\Models\Recompensa;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecompensaController extends Controller
{
    private $logicLive_recompensa;
    private $config;

    public function __construct(Recompensa $recompensa)
    {
        $this->logicLive_recompensa = new ModulosRecompensa();
        $this->config = new Configuracao();
    }

    /**
     * @return Response
     */
    public function index()
    {
        try {
            $data = Recompensa::all();
            return  ResponseController::json(Type::success, Action::index, $data);
        } catch(Throwable $e) {
            return ResponseController::json(Type::error, Action::index);
        }
    }

    /**
     * @param  Request    $request
     * @param  Recompensa $recompensa
     * @return Response
     */
    public function store(RecompensaStoreRequest $request, Recompensa $recompensa)
    {
        try {
            DB::beginTransaction();
            $recompensa->nome = $request->nome;
            $recompensa->imagem = 'nada sendo passado';
            $recompensa->pontuacao = $request->pontuacao;
            $recompensa->logic_live_id = null;
            $recompensa->save();

            if ($this->config->ativo()) {
                $criadoLogicLive = $this->logicLive_recompensa->criarRecompensa(['rec_nome' => $request->nome, 'rec_imagem' => 'nada sendo passado', 'rec_pontuacao' => $request->pontuacao]);

                if ($criadoLogicLive['success'] == false) {
                    DB::rollBack();
                    return ResponseController::json(Type::error, Action::store, null, $criadoLogicLive['msg']);
                }
                $recompensa->logic_live_id = $criadoLogicLive['data']['rec_codigo'] ;
                $recompensa->save();
            }
            DB::commit();
            return ResponseController::json(Type::success, Action::store);
        } catch(Throwable $e) {
            DB::rollBack();
            return ResponseController::json(Type::error, Action::store);
        }
    }

    /**
     * @param  int      $id
     * @return Response
     */
    public function show(int $id)
    {
        //
    }

    /**
     * @param  Request  $request
     * @param  int      $id
     * @return Response
     */
    public function update(RecompensaUpdateRequest $request, int $id)
    {
        try {
            DB::beginTransaction();
            $recompensa = Recompensa::findOrFail($id);
            $recompensa->update($request->all());
            $recompensa->save();

            if ($this->config->ativo()) {
                $criadoLogicLive = $this->logicLive_recompensa->atualizarRecompensa($recompensa->logic_live_id, ['rec_nome' => $request->nome, 'rec_imagem' => 'nada sendo passado', 'rec_pontuacao' => $request->pontuacao]);

                if ($criadoLogicLive['success'] == false) {
                    DB::rollBack();
                    return ResponseController::json(Type::error, Action::update, null, $criadoLogicLive['msg']);
                }
            }
            DB::commit();
            return ResponseController::json(Type::success, Action::update);
        } catch(Throwable $e) {
            DB::rollBack();
            return ResponseController::json(Type::error, Action::update);
        }
    }

    /**
     * @param  int      $id
     * @return Response
     */
    public function destroy(int $id)
    {
        try {
            DB::beginTransaction();
            $recompensa = Recompensa::findOrFail($id);
            $recompensa->delete();

            if ($this->config->ativo()) {
                $criadoLogicLive = $this->logicLive_recompensa->deletarRecompensa($recompensa->logic_live_id);

                if ($criadoLogicLive['success'] == false) {
                    DB::rollBack();
                    return ResponseController::json(Type::error, Action::destroy, null, $criadoLogicLive['msg']);
                }
            }
            DB::commit();
            return ResponseController::json(Type::success, Action::destroy);
        } catch(Throwable $e) {
            DB::rollBack();
            return ResponseController::json(Type::error, Action::destroy);
        }
    }
}
