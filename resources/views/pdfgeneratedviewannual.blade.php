
    <table style="width:100%;">
        <tr>
            <td style="text-align: left; width:50%;vertical-align: top;">
                <div style="background-image: url('{{$app_url}}/img/PortfolioInsider_Logo__.png');background-size: contain;background-repeat: no-repeat;padding-left:70px;">
                    <h2 style="line-height: 60px;font-size: 30px;margin-top: 0;height: 70px;"><strong>Portfolio Insider</strong></h2>
                </div>
            </td>
            <td style="text-align: right; line-height: 20px; width: 50%;">
                <div>9465 Wilshire Boulevard</div>
                <div>Office # 300</div>
                <div>Beverly Hills, CA 90212</div><br>
                <div><strong>{{ $support_phone_number }}</strong></div>
                <div>support@portfolioinsider.com</div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 30px; width:100%;">
        <tr>
            <td style="vertical-align: top;">
               <div style="color:green;font-family: 'Poppins-Light', sans-serif;margin-bottom: 10px;">INVOICE TO</div>
               <div>
                    {{ $invoice->first_name }} {{ $invoice->last_name }}
               </div>
               <div>
                    {{ $invoice->address_1 }} {{ $invoice->address_2 }}
               </div>
                <div>
                    @php
                        $inv = new \App\Http\Controllers\InvoiceGeneratorController();
                        $customer_state = '';
                        if($invoice->state != 'N/A'){
                            $customer_state = $invoice->state;
                        }
                    @endphp
                    {{ $invoice->city }} {{ $customer_state }} {{ $invoice->zip }}
                </div>
                <div>
                    {{ $phone_number }}
                </div>
                <div>
                    {{ $invoice->email }}
                </div>
            </td>
            <td style="vertical-align: top;">
                <table style="border: none;" cellspacing="0" cellpadding="0">
                    <tr>
                        <td><div style="color:green;font-family: 'Poppins-Light', sans-serif;">INVOICE NO: </div></td>
                        <td style="width: 4%;"></td>
                        <td> {{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td><div style="color:green;font-family: 'Poppins-Light', sans-serif;">INSTANT ACCESS DATE: </div></td>
                        <td style="width: 4%;"></td>
                        <td> {{ $access_date }}</td>
                    </tr>
                    @if(!empty($invoice->cc))
                        <tr>
                            <td><div style="color:green;font-family: 'Poppins-Light', sans-serif;">CC DIGITS: </div></td>
                            <td style="width: 4%;"></td>
                            <td> **********{{ $invoice->cc }}</td>
                        </tr>
                    @endif
                </table>
            </td>
            <td></td>
        </tr>
    </table>

    <table style="margin-top: 20px;width: 100%;">
        <tr>
            <td>
                <div style="text-align: center;color:white;background-color: #194CAF;font-size: 22px;padding: 7px 0;">
                    Item Delivery Description
                </div>
            </td>
            <td>
                <div style="text-align: center;color:white;background-color: #194CAF;font-size: 22px;padding: 7px 0;">
                    Price
                </div>
            </td>
            <td>
                <div style="text-align: center;color:white;background-color: #194CAF;font-size: 22px;padding: 7px 0;">
                    Quantity
                </div>
            </td>
            <td>
                <div style="text-align: center;color:white;background-color: #194CAF;font-size: 22px;padding: 7px 0;">
                    Total
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div style="margin-top: 10px;">
                    <strong>Benefit from over 200 institutional grade financial API's</strong>
                </div>
                <div style="margin-top: 10px;">
                    Access to follow billionaires and portfolios including quarterly holdings of institutions.
                </div>
                <div style="margin-top: 10px;">
                    Market commentary by MIT Phd's team "Daily Digest".
                </div>
                <div style="margin-top: 10px;">
                    Proprietary rank for stocks, mutual funds & ETF's including access to closed ended mutual funds.
                </div>
                <div style="margin-top: 10px;">
                    Enhance performance with the most powerful earnings estimate analytics system available.
                </div>
            </td>
            <td style="vertical-align: top;">
                <div style="text-align: center;margin-top: 10px;">
                    {{$total_before_discount}}
                </div>
            </td>
            <td style="vertical-align: top;">
                <div style="text-align: center;margin-top: 10px;">
                    1
                </div>
            </td>
            <td style="vertical-align: top;">
                <div style="text-align: center;margin-top: 10px;">
                    <strong>{{$total_before_discount}}</strong>
                </div>
            </td>
        </tr>
        @if($invoice->discount_total > 0 && !empty($discounts) && count($discounts))
            @foreach($discounts as $d)
                @if($d['amount'] > 0)
                    <tr>
                        <td colspan="3">
                            <div style="color:red;text-align: right;">
                                <strong>{{strtoupper($d['title'])}}</strong>
                            </div>
                        </td>
                        <td>
                            <div style="color:red;text-align: left;">
                                <strong>-{{$inv->moneyFormat($d['amount'])}}</strong>
                            </div>
                        </td>
                    </tr>
                @endif
            @endforeach
        @endif
        <tr>
            <td></td>
            <td></td>
            <td>
                <div style="color:green;text-align: right;">
                    <strong>GRAND TOTAL</strong>
                </div>
            </td>
            <td>
                <div style="color:green;text-align: left;">
                    <strong>&nbsp;{{$grand_total}}</strong>
                </div>
            </td>
        </tr>
        @if(($invoice->grand_total - $invoice->paid) > 0)
            <tr>
                <td colspan="4"><hr style="border: none;height: 1px;color: #333;background-color: #333;"></td>
            </tr>
            <tr>
                <td></td>
                <td colspan="2">
                    <div style="color:red;text-align: right;">
                        <strong>PENDING</strong>
                    </div>
                </td>
                <td>
                    <div style="color:red;text-align: left;">
                        <strong>-{{ $inv->moneyFormat($invoice->grand_total - $invoice->paid) }}</strong>
                    </div>
                </td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td>
                    <div style="color:green;text-align: right;">
                        <strong>PAID</strong>
                    </div>
                </td>
                <td>
                    <div style="color:green;text-align: left;">
                        <strong>&nbsp;{{$inv->moneyFormat($invoice->paid)}}</strong>
                    </div>
                </td>
            </tr>
        @endif
    </table>
