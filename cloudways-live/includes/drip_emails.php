<?php
/**
 * GetAIWired - Drip Email Templates & Schedule
 * 5-email nurture sequence after AI Readiness Assessment
 */

// Hours after enrollment for each email
define('DRIP_SCHEDULE', [
    1 => ['hours' => 12],    // 12 hours after assessment
    2 => ['hours' => 72],    // Day 3
    3 => ['hours' => 144],   // Day 6
    4 => ['hours' => 240],   // Day 10
    5 => ['hours' => 336],   // Day 14
]);

define('CALENDLY_URL', 'https://calendly.com/admin-intellismartco/30min');

/**
 * Get the email content for a given stage
 * @return array ['subject' => string, 'html' => string, 'text' => string]
 */
function getDripEmail($emailNumber, $campaign) {
    $firstName = htmlspecialchars($campaign['first_name'] ?: 'there');
    $company = htmlspecialchars($campaign['company'] ?: 'your company');
    $score = intval($campaign['score']);
    $level = htmlspecialchars($campaign['level'] ?: 'Emerging');
    $topOpp = htmlspecialchars($campaign['top_opportunity'] ?: 'process automation');
    $industry = htmlspecialchars($campaign['industry'] ?: 'your');
    $unsubToken = $campaign['unsubscribe_token'];
    $unsubUrl = "https://getaiwired.com/api/unsubscribe.php?token=" . urlencode($unsubToken);

    $opportunities = [];
    if (!empty($campaign['opportunities_json'])) {
        $opportunities = json_decode($campaign['opportunities_json'], true) ?: [];
    }

    switch ($emailNumber) {
        case 1:
            return buildEmail1($firstName, $company, $score, $level, $topOpp, $opportunities, $unsubUrl);
        case 2:
            return buildEmail2($firstName, $company, $industry, $topOpp, $opportunities, $unsubUrl);
        case 3:
            return buildEmail3($firstName, $company, $score, $unsubUrl);
        case 4:
            return buildEmail4($firstName, $company, $score, $level, $unsubUrl);
        case 5:
            return buildEmail5($firstName, $company, $unsubUrl);
        default:
            return null;
    }
}

// =====================================================
// EMAIL WRAPPER
// =====================================================

function wrapDripHtml($content, $unsubUrl) {
    return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background: #f3f4f6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px;">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 24px;">
            <img src="https://getaiwired.com/images/getaiwired-logo-transparent.png" alt="GetAIWired" style="height: 60px; margin-bottom: 8px;">
        </div>

        ' . $content . '

        <!-- Footer -->
        <div style="text-align: center; color: #9ca3af; font-size: 12px; margin-top: 32px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0 0 8px;">GetAIWired.com &middot; IntelliSmart AI</p>
            <p style="margin: 0 0 8px;">Questions? Email <a href="mailto:support@intellismartai.com" style="color: #6366f1;">support@intellismartai.com</a></p>
            <p style="margin: 0;"><a href="' . $unsubUrl . '" style="color: #9ca3af; text-decoration: underline;">Unsubscribe</a></p>
        </div>
    </div>
</body>
</html>';
}

function ctaButton($text = 'Book a Free Strategy Call') {
    return '<div style="text-align: center; margin: 28px 0;">
        <a href="' . CALENDLY_URL . '" style="display: inline-block; background: linear-gradient(135deg, #6366f1, #7c3aed); color: white; padding: 14px 32px; border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 14px;">' . $text . ' &rarr;</a>
    </div>';
}

// =====================================================
// EMAIL 1: Your AI roadmap starts here (12 hours)
// =====================================================

function buildEmail1($firstName, $company, $score, $level, $topOpp, $opportunities, $unsubUrl) {
    $subject = "Your AI roadmap starts here, $firstName";

    // Build top 3 opportunities list
    $oppsHtml = '';
    $oppsText = '';
    $topOpps = array_slice($opportunities, 0, 3);
    foreach ($topOpps as $i => $opp) {
        $title = htmlspecialchars($opp['title'] ?? '');
        $desc = htmlspecialchars($opp['description'] ?? '');
        $num = $i + 1;
        $oppsHtml .= "<tr>
            <td style=\"padding: 12px 16px; border-bottom: 1px solid #e5e7eb;\">
                <strong style=\"color: #1f2937;\">$num. $title</strong><br>
                <span style=\"color: #6b7280; font-size: 13px;\">$desc</span>
            </td>
        </tr>";
        $oppsText .= "$num. " . ($opp['title'] ?? '') . " - " . ($opp['description'] ?? '') . "\n";
    }

    $html = '<div style="background: white; border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h1 style="margin: 0 0 16px; color: #1f2937; font-size: 22px;">Hi ' . $firstName . ', let\'s put your AI score to work</h1>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Yesterday you took the AI Readiness Assessment for <strong>' . $company . '</strong> and scored <strong>' . $score . '%</strong> (' . $level . ' level). That puts you ahead of most businesses &mdash; but there\'s real opportunity to go further.
        </p>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Here are your <strong>top 3 AI opportunities</strong> and what each one means for your business:
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
            <tbody>' . $oppsHtml . '</tbody>
        </table>

        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Each of these can be tackled in a focused pilot &mdash; no massive overhaul required. The fastest way to figure out which one to start with? A quick strategy call.
        </p>

        ' . ctaButton() . '
    </div>';

    $text = "Hi $firstName,

Yesterday you took the AI Readiness Assessment for $company and scored $score% ($level level).

Here are your top 3 AI opportunities:

$oppsText
Each of these can be tackled in a focused pilot. The fastest way to figure out which one to start with? A quick strategy call.

Book a free strategy call: " . CALENDLY_URL . "

- The GetAIWired Team";

    return [
        'subject' => $subject,
        'html' => wrapDripHtml($html, $unsubUrl),
        'text' => $text,
    ];
}

// =====================================================
// EMAIL 2: The #1 AI quick win (Day 3)
// =====================================================

function buildEmail2($firstName, $company, $industry, $topOpp, $opportunities, $unsubUrl) {
    $subject = "The #1 AI quick win for $industry businesses";

    // Find the lowest-effort, highest-impact opportunity
    $quickWin = $topOpp;
    $quickWinDesc = '';
    foreach ($opportunities as $opp) {
        if (($opp['effort'] ?? '') === 'Low' && ($opp['impact'] ?? '') === 'High') {
            $quickWin = htmlspecialchars($opp['title'] ?? $topOpp);
            $quickWinDesc = htmlspecialchars($opp['description'] ?? '');
            break;
        }
    }
    if (empty($quickWinDesc) && !empty($opportunities[0])) {
        $quickWinDesc = htmlspecialchars($opportunities[0]['description'] ?? '');
    }

    $html = '<div style="background: white; border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h1 style="margin: 0 0 16px; color: #1f2937; font-size: 22px;">The fastest AI win for ' . $company . '</h1>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Hi ' . $firstName . ',
        </p>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Based on your assessment, the single biggest quick win for ' . $company . ' is <strong>' . $quickWin . '</strong>.
        </p>
        ' . (!empty($quickWinDesc) ? '<p style="color: #4b5563; font-size: 15px; line-height: 1.6;">' . $quickWinDesc . '</p>' : '') . '

        <div style="background: #f0fdf4; border-left: 4px solid #22c55e; padding: 16px 20px; border-radius: 0 8px 8px 0; margin: 20px 0;">
            <strong style="color: #166534;">Why this matters:</strong>
            <p style="color: #166534; margin: 8px 0 0; font-size: 14px;">
                Most ' . $industry . ' businesses spend 10-20 hours/week on tasks AI can automate. A single quick-win automation typically saves 5-8 hours/week and pays for itself in under 60 days.
            </p>
        </div>

        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Want to see exactly how this would work for ' . $company . '? I can walk you through it in 15 minutes.
        </p>

        ' . ctaButton('See How It Works') . '
    </div>';

    $text = "Hi $firstName,

Based on your assessment, the single biggest quick win for $company is: $quickWin

$quickWinDesc

Most $industry businesses spend 10-20 hours/week on tasks AI can automate. A single quick-win automation typically saves 5-8 hours/week and pays for itself in under 60 days.

Want to see exactly how this would work for $company?

Book a call: " . CALENDLY_URL . "

- The GetAIWired Team";

    return [
        'subject' => $subject,
        'html' => wrapDripHtml($html, $unsubUrl),
        'text' => $text,
    ];
}

// =====================================================
// EMAIL 3: What a $5K AI pilot looks like (Day 6)
// =====================================================

function buildEmail3($firstName, $company, $score, $unsubUrl) {
    $subject = "What a \$5K AI pilot looks like";

    $html = '<div style="background: white; border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h1 style="margin: 0 0 16px; color: #1f2937; font-size: 22px;">What a $5K AI pilot actually looks like</h1>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Hi ' . $firstName . ',
        </p>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            A lot of businesses think AI adoption means a six-figure project and months of work. It doesn\'t have to.
        </p>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Our <strong>Starter Sprint</strong> is a focused 2-week pilot designed to deliver one measurable AI win:
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; width: 40%;">
                    <strong style="color: #6366f1;">Week 1</strong>
                </td>
                <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #4b5563;">
                    Discovery &amp; setup &mdash; we map your workflow, pick the highest-ROI automation, and build it
                </td>
            </tr>
            <tr>
                <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
                    <strong style="color: #6366f1;">Week 2</strong>
                </td>
                <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #4b5563;">
                    Testing &amp; handoff &mdash; your team uses it, we refine it, you keep it
                </td>
            </tr>
            <tr>
                <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb;">
                    <strong style="color: #6366f1;">Typical ROI</strong>
                </td>
                <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #4b5563;">
                    5-10 hours/week saved, payback in 30-60 days
                </td>
            </tr>
            <tr>
                <td style="padding: 12px 16px;">
                    <strong style="color: #6366f1;">Investment</strong>
                </td>
                <td style="padding: 12px 16px; color: #4b5563;">
                    Starting at $5,000
                </td>
            </tr>
        </table>

        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            With ' . $company . '\'s score of ' . $score . '%, you\'re in a great position to see fast results from a pilot like this.
        </p>

        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Want to talk through whether a Starter Sprint makes sense for ' . $company . '?
        </p>

        ' . ctaButton('Let\'s Talk') . '
    </div>';

    $text = "Hi $firstName,

A lot of businesses think AI adoption means a six-figure project. It doesn't have to.

Our Starter Sprint is a focused 2-week pilot:

- Week 1: Discovery & setup - map your workflow, pick the highest-ROI automation, build it
- Week 2: Testing & handoff - your team uses it, we refine it, you keep it
- Typical ROI: 5-10 hours/week saved, payback in 30-60 days
- Investment: Starting at \$5,000

With $company's score of $score%, you're in a great position for fast results.

Want to talk through whether a Starter Sprint makes sense?

Book a call: " . CALENDLY_URL . "

- The GetAIWired Team";

    return [
        'subject' => $subject,
        'html' => wrapDripHtml($html, $unsubUrl),
        'text' => $text,
    ];
}

// =====================================================
// EMAIL 4: Your AI score vs. the average (Day 10)
// =====================================================

function buildEmail4($firstName, $company, $score, $level, $unsubUrl) {
    $subject = "Your AI score vs. the average";

    $avg = 38; // industry average benchmark
    $diff = $score - $avg;
    $comparison = $diff > 0
        ? "That's <strong>" . $diff . " points above</strong> the average"
        : "That's close to the average";

    $html = '<div style="background: white; border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h1 style="margin: 0 0 16px; color: #1f2937; font-size: 22px;">How ' . $company . ' stacks up on AI readiness</h1>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Hi ' . $firstName . ',
        </p>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Across hundreds of assessments, the average AI Readiness Score is <strong>' . $avg . '%</strong>. ' . $company . ' scored <strong>' . $score . '%</strong> (' . $level . '). ' . $comparison . '.
        </p>

        <div style="background: #eef2ff; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center;">
            <div style="display: inline-block; margin: 0 24px; text-align: center;">
                <div style="font-size: 36px; font-weight: 800; color: #6366f1;">' . $score . '%</div>
                <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">' . $company . '</div>
            </div>
            <div style="display: inline-block; margin: 0 24px; text-align: center;">
                <div style="font-size: 36px; font-weight: 800; color: #9ca3af;">' . $avg . '%</div>
                <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">Average</div>
            </div>
        </div>

        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Here\'s the thing: your competitors are moving. Businesses that start with AI now typically see a <strong>2-3x advantage</strong> in efficiency within 6 months. The gap between early adopters and everyone else is widening fast.
        </p>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            The good news? You don\'t need to overhaul everything. One focused automation can put ' . $company . ' ahead of the curve.
        </p>

        ' . ctaButton('Get Ahead of the Curve') . '
    </div>';

    $text = "Hi $firstName,

Across hundreds of assessments, the average AI Readiness Score is $avg%. $company scored $score% ($level).

Your competitors are moving. Businesses that start with AI now typically see a 2-3x advantage in efficiency within 6 months.

The good news? One focused automation can put $company ahead of the curve.

Book a call: " . CALENDLY_URL . "

- The GetAIWired Team";

    return [
        'subject' => $subject,
        'html' => wrapDripHtml($html, $unsubUrl),
        'text' => $text,
    ];
}

// =====================================================
// EMAIL 5: Still thinking it over? (Day 14)
// =====================================================

function buildEmail5($firstName, $company, $unsubUrl) {
    $subject = "Still thinking it over, $firstName?";

    $html = '<div style="background: white; border-radius: 16px; padding: 32px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h1 style="margin: 0 0 16px; color: #1f2937; font-size: 22px;">One last thing, ' . $firstName . '</h1>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Hi ' . $firstName . ',
        </p>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Two weeks ago you took the AI Readiness Assessment for ' . $company . '. Since then, I\'ve been sharing ideas about where AI could make the biggest difference for your business.
        </p>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            If you\'re still thinking about it, that\'s totally fine. AI is a big decision and I want you to feel confident about the path forward.
        </p>
        <p style="color: #4b5563; font-size: 15px; line-height: 1.6;">
            Here\'s what I\'d suggest: <strong>a free 15-minute walkthrough of your report</strong>. No pitch, no pressure &mdash; just a conversation about what the data says and whether there\'s a realistic next step.
        </p>

        <div style="background: #f5f3ff; border-radius: 12px; padding: 20px; margin: 20px 0;">
            <p style="margin: 0; color: #4b5563; font-size: 14px;">
                <strong style="color: #6366f1;">What you\'ll get in 15 minutes:</strong><br>
                &bull; Walkthrough of your AI Readiness Report<br>
                &bull; Honest assessment of what\'s worth pursuing now<br>
                &bull; A clear next step (even if it\'s "wait 6 months")
            </p>
        </div>

        ' . ctaButton('Book Your Free Walkthrough') . '

        <p style="color: #9ca3af; font-size: 13px; line-height: 1.6; margin-top: 24px;">
            This is the last email in this series. If now isn\'t the right time, no worries at all &mdash; your report will always be valid. Just reply to this email whenever you\'re ready to chat.
        </p>
    </div>';

    $text = "Hi $firstName,

Two weeks ago you took the AI Readiness Assessment for $company. If you're still thinking about it, that's totally fine.

Here's what I'd suggest: a free 15-minute walkthrough of your report. No pitch, no pressure.

What you'll get in 15 minutes:
- Walkthrough of your AI Readiness Report
- Honest assessment of what's worth pursuing now
- A clear next step (even if it's \"wait 6 months\")

Book your free walkthrough: " . CALENDLY_URL . "

This is the last email in this series. Your report is always valid - just reply whenever you're ready.

- The GetAIWired Team";

    return [
        'subject' => $subject,
        'html' => wrapDripHtml($html, $unsubUrl),
        'text' => $text,
    ];
}
?>
