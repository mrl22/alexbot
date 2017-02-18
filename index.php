<?php
date_default_timezone_set('Europe/London');

define('BOT_NAME', 'Alex');
define('DATE_FORMAT', 'H:i:s');

session_start();
if ($_GET['clear']) {
    unset($_SESSION['messages']);
    session_write_close();
    header('location: ./');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ajax Request to Bot

    $message = $_POST['message'];
    $brain = json_decode(@file_get_contents('brain.txt'), true);
    if (!is_array($brain)) $brain = array();

    add_message('You', $message);

    // Simple Facts and Questions
    if (is_question($message)) {}
    elseif(is_fact($message)) {}
    elseif(is_forget($message)) {}

    //
    elseif(is_general_conduct($message)) {}
    else do_not_understand($message);

    file_put_contents('brain.txt', json_encode($brain));
    exit;
}

function is_question($message) {
    // Asked a simple question

    // Ignoring 'am'
    preg_match('/^(who |what |where |how |when |why )(.*)(is |are |do )(.+)\?$/i', $message, $matches);
    if (count($matches)) {
//        print_r($matches);
        global $brain;
        $found = array();
        if (count($brain))
            foreach ($brain as $item)
                if (strtolower($matches[4]) == $item['who'])
                    $found[] = $item;
        shuffle($found);

        if (count($found)) {
            add_message(BOT_NAME, 'It seems that '.$found[0]['who'].' '.$found[0]['delimiter'].' '.$found[0]['answer'].'.');
        } else {
            add_message(BOT_NAME, 'Sorry, I do not know anything about '.$matches[4].'.');
        }

        return true;
    } else return false;

}

function is_fact($message) {
    // Told a simple fact

    preg_match('/^(.+)( is | are |eat |drink |sleep |walk )(.+)$/i', $message, $matches);
    if (count($matches)) {
        global $brain;
        $brain[] = array(
            'who' => $matches[1],
            'delimiter' => str_replace(' ', '', $matches[2]),
            'answer' => $matches[3]
        );
        add_message(BOT_NAME, 'OK, Understood.');
        return true;
    }
    else return false;
}

function is_forget($message) {
    // Told to forget something

    preg_match('/^(forget about |forget )(.+)$/i', $message, $matches);
    if (count($matches)) {
        global $brain;
        $found = false;
        if (count($brain))
            foreach ($brain as $key => $item)
                if (strtolower($matches[2]) == $item['who']) {
                    unset($brain[$key]);
                    $found = true;
                }

                print_r($matches);


        if ($found == true) {
            add_message(BOT_NAME, 'I have '.str_replace('orget', 'orgotten',$matches[1]).$matches[2].'.');
        } else {
            add_message(BOT_NAME, 'Sorry, I do not know anything about '.$matches[2].'.');
        }

        return true;
    } else return false;

}

function is_general_conduct($message) {
    // Something generic such as hello

}


function do_not_understand($message) {
    add_message(BOT_NAME, 'Sorry, I do not understand.');
    return true;
}

function add_message($who, $message, $echo=true)
{
    $timestamp = time();
    $_SESSION['messages'][$timestamp] = array(
        'who' => $who,
        'message' => $message
    );

    if ($echo) echo date(DATE_FORMAT, $timestamp) . ' [' . $who . '] ' . $message . "\n";

}

function get_messages()
{
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = array();
        add_message(BOT_NAME, 'Hello! My name is ' . BOT_NAME . '. I am a semi-dumb programmable bot, you can ask or tell me anything that you want.', false);
    }

    foreach ($_SESSION['messages'] as $timestamp => $data) {
        extract($data);
        echo date(DATE_FORMAT, $timestamp) . ' [' . $who . '] ' . $message . "\n";
    }
}

?>
    <!DOCTYPE html>
    <html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo BOT_NAME; ?>Bot</title>
    <script src="jquery-3.1.1.min.js"></script>
    <style>
        body {
            font-family: "Trebuchet MS", Helvetica, sans-serif;
        }

        input[type=text] {
            width: 500px;
        }
    </style>
</head>
<body>
<h1><?php echo BOT_NAME; ?>Bot</h1>
<form>
    Chat <input name="message"  type="text" value="" autocomplete="off">
    <input name="submit" type="submit" value="Send">
    <a href="?clear=1">Clear Chat</a>
    <pre class="chat"><?php get_messages(); ?></pre>
</form>
<script>
    (function ($) {
        $('form').on('submit', function (e) {
            if ($('input[type=text]').val().length) {
                $.post('<?php echo basename(__FILE__); ?>', $('form').serialize(), function (data) {
                    $('pre').append(data);
                });
            }
            $('input[type=text]').select();
            e.preventDefault();
        });
    })(jQuery);
</script>
</body>
</html>