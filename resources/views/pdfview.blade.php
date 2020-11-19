
    <table style="margin-top: 20px; width:100%;">
        <tr>
            <td style="text-align: left; width:50%;">
                <div style="background-image: url('{{$app_url}}/img/PortfolioInsider_Logo__.png');background-size: contain;background-repeat: no-repeat;padding-left:70px;">
                    <h2 style="line-height: 70px;font-size: 30px;"><strong>Portfolio Insider</strong></h2>
                </div>
            </td>
            <td style="text-align: right; line-height: 20px; width: 50%;">
                <div>9465 Wilshire Boulevard</div>
                <div>Office # 300</div>
                <div>Beverly Hills, CA 90212</div><br>
                <div><strong>1-866-980-2909</strong></div>
                <div>support@portfolioinsider.com</div>
            </td>
        </tr>
    </table>
    <table style="margin-top: 70px; width:100%;">
        <tr>
            <th>No</th>
            <th>First Name</th>
            <th>Last Name</th>
        </tr>

            <tr>
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->customer->first_name }}</td>
                <td>{{ $invoice->customer->last_name }}</td>
            </tr>

    </table>
