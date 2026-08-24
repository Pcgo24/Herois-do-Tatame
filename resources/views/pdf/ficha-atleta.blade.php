@php
    use App\Support\Formatters;
    use Illuminate\Support\Str;

    $meses = [
        1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
    ];

    $dia = $issuedAt->format('d');
    $mes = $meses[(int) $issuedAt->format('n')];
    $ano = $issuedAt->format('Y');

    $filiacao = fn (?string $nome, bool $naoPossui) => $naoPossui ? 'Não declarado' : ($nome ?: '');

    $logo = public_path('img/prudentopolis-smer.png');
    $temLogo = is_file($logo);
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Ficha de Cadastro de Atleta — {{ $student->name }}</title>
    <style>
        @page { margin: 25mm 18mm; }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10.5pt;
            color: #000;
            line-height: 1.45;
        }
        .cabecalho { width: 100%; margin-bottom: 26px; }
        .cabecalho td { vertical-align: middle; }
        .cabecalho img { width: 100%; max-width: 460px; }
        .municipio { font-size: 7pt; letter-spacing: 2px; color: #444; }
        .municipio strong { display: block; font-size: 17pt; letter-spacing: 0; color: #000; }
        .smer {
            background: #4a4a4a;
            color: #fff;
            padding: 6px 12px;
            font-size: 11pt;
            font-weight: bold;
            line-height: 1.15;
            text-align: center;
        }
        .smer span { display: block; font-size: 6.5pt; font-weight: normal; letter-spacing: 1px; }
        h1 { font-size: 12pt; font-weight: normal; text-align: center; margin: 0 0 4px; }
        h2 { font-size: 11pt; font-weight: normal; text-align: center; margin: 0 0 12px; }
        table.dados { width: 100%; border-collapse: collapse; margin-bottom: 26px; }
        table.dados th {
            border: 1px solid #000;
            font-weight: normal;
            text-align: center;
            padding: 3px 6px;
        }
        table.dados td { border: 1px solid #000; padding: 4px 6px; }
        .valor { font-weight: bold; }
        h3 { font-size: 11pt; font-weight: normal; text-align: center; margin: 0 0 12px; }
        p.autorizacao { text-align: justify; text-indent: 40px; margin: 0 0 16px; }
        p.declaracao { text-indent: 40px; margin: 0 0 40px; }
        p.local { text-indent: 40px; margin: 0 0 60px; }
        table.assinaturas { width: 100%; margin-bottom: 30px; }
        table.assinaturas td { width: 50%; text-align: center; padding: 0 10px; }
        .linha { border-top: 1px solid #000; padding-top: 4px; }
        p.obs { font-size: 9pt; margin: 0; }
    </style>
</head>
<body>

    @if ($temLogo)
        <table class="cabecalho">
            <tr>
                <td style="text-align: center;"><img src="{{ $logo }}" alt="Município de Prudentópolis — Secretaria de Esportes e Recreação"></td>
            </tr>
        </table>
    @else
        <table class="cabecalho">
            <tr>
                <td class="municipio">MUNICÍPIO DE<strong>PRUDENTÓPOLIS</strong></td>
                <td style="text-align: right;">
                    <div class="smer"><span>SECRETARIA DE</span>ESPORTES E RECREAÇÃO</div>
                </td>
            </tr>
        </table>
    @endif

    <h1>FICHA DE CADASTRO DE ATLETA</h1>
    <h2>{{ Str::upper($student->modalidade) }} &ndash; {{ $ano }}</h2>

    <table class="dados">
        <tr>
            <th colspan="4">DADOS DO ATLETA</th>
        </tr>
        <tr>
            <td colspan="4">Nome do Atleta: <span class="valor">{{ $student->name }}</span></td>
        </tr>
        <tr>
            <td colspan="2">R.G.: <span class="valor">{{ Formatters::rg($student->rg) }}</span></td>
            <td colspan="2">Data Nasc.: <span class="valor">{{ Formatters::date($student->birth_date) }}</span></td>
        </tr>
        <tr>
            <td colspan="2">Escola: <span class="valor">{{ $student->school }}</span></td>
            <td colspan="2">Série: <span class="valor">{{ $student->grade }}</span></td>
        </tr>
        <tr>
            <td colspan="4">Filiação Pai: <span class="valor">{{ $filiacao($student->father_name, $student->no_father) }}</span></td>
        </tr>
        <tr>
            <td colspan="4">Mãe: <span class="valor">{{ $filiacao($student->mother_name, $student->no_mother) }}</span></td>
        </tr>
        <tr>
            <td colspan="3">Endereço: <span class="valor">{{ $responsible->address }}</span></td>
            <td>Bairro: <span class="valor">{{ $responsible->neighborhood }}</span></td>
        </tr>
        <tr>
            <td>Fone resid.: <span class="valor">{{ Formatters::phone($responsible->home_phone) }}</span></td>
            <td>Cel pais: <span class="valor">{{ Formatters::phone($responsible->phone_number) }}</span></td>
            <td colspan="2">Cel atleta: <span class="valor">{{ Formatters::phone($student->phone) }}</span></td>
        </tr>
        <tr>
            <td colspan="2">e-mail atleta: <span class="valor">{{ $student->email }}</span></td>
            <td colspan="2">Pais: <span class="valor">{{ $responsible->email }}</span></td>
        </tr>
    </table>

    <h3>AUTORIZAÇÃO DE PARTICIPAÇÃO</h3>

    <p class="autorizacao">
        Eu, <strong>{{ $responsible->name }}</strong>, portador da Cédula de Identidade RG nº
        <strong>{{ Formatters::rg($responsible->rg) }}</strong>, na qualidade de pai (mãe) ou responsável pelo menor
        <strong>{{ $student->name }}</strong>, portador da Cédula de Identidade RG nº
        <strong>{{ Formatters::rg($student->rg) }}</strong>, declaro que o mesmo está autorizado a participar dos
        treinos e competições organizados pela Secretaria Municipal de Esportes e Recreação &ndash; SMER &ndash; e que
        goza de perfeita saúde para a prática esportiva, isentando a SMER, seus professores e demais envolvidos de
        quaisquer problemas oriundos de acidentes ou outras ocorrências no decorrer dos treinos ou competições.
    </p>

    <p class="declaracao">Pela verdade, firmo a presente declaração.</p>

    <p class="local">Prudentópolis, {{ $dia }} de {{ $mes }} de {{ $ano }}.</p>

    <table class="assinaturas">
        <tr>
            <td><div class="linha">Assinatura do pai ou responsável</div></td>
            <td><div class="linha">Assinatura do atleta</div></td>
        </tr>
    </table>

    <p class="obs">
        <strong>Obs:</strong> O atleta só poderá participar dos treinos após entregar a ficha preenchida
        e assinada ao professor.
    </p>

</body>
</html>
