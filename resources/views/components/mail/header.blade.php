<tr>
    <td class="header">
        @if ($url ?? false)
            <a href="{{ $url }}" style="display: inline-block;">
                {{ $slot }}
            </a>
        @else
            {{ $slot }}
        @endif
    </td>
</tr>
