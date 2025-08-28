const colorsPalette = [
    { bg: "#E3F2FD", color: "#0D47A1" }, // azul suave
    { bg: "#F1F8E9", color: "#33691E" }, // verde suave
    { bg: "#FFF3E0", color: "#E65100" }, // laranja suave
    { bg: "#FCE4EC", color: "#880E4F" }, // rosa suave
    { bg: "#EDE7F6", color: "#311B92" }, // roxo suave
    { bg: "#E0F2F1", color: "#004D40" }, // teal suave
    { bg: "#FFFDE7", color: "#F57F17" }, // amarelo suave
    { bg: "#ECEFF1", color: "#263238" }, // cinza suave
];

export function getAvatarStyle(index) {
    const pal = colorsPalette[index % colorsPalette.length];

    const rgb = pal.color
        .substring(1)
        .match(/.{2}/g)
        .map((x) => parseInt(x, 16))
        .join(", ");

    let marginLeft = '0';

    if (index !== 0) {
        marginLeft = '-8px';
    }

    return {
        "background-color": pal.bg,
        color: pal.color,
        "border": `1px solid rgba(${rgb}, 0.5)`,
        width: '40px',
        height: '40px',
        "margin-left": marginLeft,
        cursor: 'pointer'
    };
}
