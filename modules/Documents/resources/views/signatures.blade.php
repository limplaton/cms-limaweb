    <h5 style="margin-bottom:10px;padding-left:8px;font-size:16px;">
        {{ __('documents::document.signature.signatures') }}
    </h5>

    <table style="width:100%;">
        <thead>
            <tr style="border-bottom:1px solid #f0f0f0;">
                <th style="text-align: left; padding:8px; font-size:14px; font-weight:bold;">
                    {{ __('documents::document.signers.signer_name') }}
                </th>
                <th style="text-align: left; padding:8px; font-size:14px; font-weight:bold;">
                    {{ __('documents::document.signers.signature_date') }}
                </th>
                <th style="text-align: left; padding:8px; font-size:14px; font-weight:bold;">
                    {{ __('documents::document.signature.sign_ip') }}
                </th>
                <th style="text-align: left; padding:8px; font-size:14px; font-weight:bold;">
                    {{ __('documents::document.signature.signature') }}
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($document->signers->filter->hasSignature() as $signer)
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="text-align: left;padding:8px;">
                        {{ $signer->name }}
                    </td>
                    <td style="text-align: left;padding:8px;">
                        {{ $signer->signed_at }} ({{ config('app.timezone') }})
                    </td>
                    <td style="text-align: left;padding:8px;">
                        {{ $signer->sign_ip }}
                    </td>
                    <td class="font-signature"
                        style="font-size: 28px;text-align: left;vertical-align: middle;padding:8px;">
                        {{ $signer->signature }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
